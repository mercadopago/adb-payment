#!/bin/bash
# Pre-stop quality checks for modified PHP files

MODIFIED=$(git diff --name-only HEAD | grep '\.php$')

if [ -z "$MODIFIED" ]; then
  echo '{"continue":true}'
  exit 0
fi

FAIL=0
MSGS=()

# Check 1: no debug statements
for f in $MODIFIED; do
  if grep -qE 'var_dump|print_r|error_log' "$f" 2>/dev/null; then
    MSGS+=("❌ Debug statement found in $f")
    FAIL=1
  fi
done

# Check 2: no TODO without ticket reference
for f in $MODIFIED; do
  if grep -qE 'TODO[^:]*$|TODO:[^A-Z]*$' "$f" 2>/dev/null; then
    if grep -E 'TODO' "$f" | grep -qvE 'TODO.*[A-Z]+-[0-9]+'; then
      MSGS+=("❌ TODO sem referência de ticket em $f")
      FAIL=1
    fi
  fi
done

# Check 3: PHPDoc on public methods
for f in $MODIFIED; do
  if grep -q 'public function' "$f" 2>/dev/null; then
    while IFS= read -r line; do
      lineno=$line
      docline=$((lineno - 1))
      context=$(sed -n "${docline}p" "$f" 2>/dev/null)
      if ! echo "$context" | grep -qE '(@\w+|\*/)'; then
        MSGS+=("❌ PHPDoc ausente antes de 'public function' na linha $lineno de $f")
        FAIL=1
        break
      fi
    done < <(grep -n 'public function' "$f" | cut -d: -f1)
  fi
done

# Check 4: PHPCS (skip gracefully if Docker unavailable)
if docker inspect magento-php >/dev/null 2>&1; then
  for f in $MODIFIED; do
    OUT=$(docker exec magento-php magento2/vendor/bin/phpcs -q --report=full --standard=Magento2 "magento2/app/code/MercadoPago/AdbPayment/$f" 2>&1)
    if [ -n "$OUT" ]; then
      MSGS+=("❌ PHPCS violation in $f: $OUT")
      FAIL=1
    fi
  done
else
  MSGS+=("⚠️  PHPCS skipped — Docker container magento-php not running")
fi

if [ $FAIL -eq 1 ]; then
  MSG=$(printf '%s\n' "${MSGS[@]}")
  ESCAPED=$(echo "$MSG" | python3 -c "import sys,json; print(json.dumps(sys.stdin.read()))")
  echo "{\"continue\":false,\"stopReason\":$ESCAPED}"
else
  if [ ${#MSGS[@]} -gt 0 ]; then
    NOTE=$(printf '%s\n' "${MSGS[@]}")
    ESCAPED_NOTE=$(echo "$NOTE" | python3 -c "import sys,json; print(json.dumps(sys.stdin.read()))")
    echo "{\"continue\":true,\"systemMessage\":$ESCAPED_NOTE}"
  else
    echo '{"continue":true}'
  fi
fi
