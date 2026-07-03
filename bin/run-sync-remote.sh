#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$ROOT_DIR/.env.remote"

if [ ! -f "$ENV_FILE" ]; then
    echo "Erro: $ENV_FILE nao encontrado. Copie .env.remote.sample e preencha."
    exit 1
fi

# shellcheck source=/dev/null
source "$ENV_FILE"

SYNC_ALL=false
[[ "${1:-}" == "--all" ]] && SYNC_ALL=true

SSH_OPTS="-i $REMOTE_SSH_KEY -o StrictHostKeyChecking=accept-new -o BatchMode=yes"

echo ""
echo "🔄 Sincronizando com $REMOTE_HOST..."
echo ""

if $SYNC_ALL; then
    echo "Modo: completo (tar pipe)"
    cd "$ROOT_DIR"
    tar czf - \
        --exclude='.git' \
        --exclude='vendor' \
        --exclude='node_modules' \
        --exclude='e2e' \
        --exclude='build' \
        --exclude='.env.remote' \
        --exclude='release-*' \
        --exclude='.claude/worktrees' \
        --exclude='meli' \
        . | ssh $SSH_OPTS "$REMOTE_USER@$REMOTE_HOST" "mkdir -p $REMOTE_PATH && cd $REMOTE_PATH && tar xzf -"
else
    echo "Modo: arquivos modificados no git"
    CHANGED=$(
        {
            git -C "$ROOT_DIR" diff --name-only HEAD 2>/dev/null
            git -C "$ROOT_DIR" ls-files --others --exclude-standard 2>/dev/null
        } | grep -v '^vendor/' \
          | grep -v '^node_modules/' \
          | grep -v '^e2e/' \
          | grep -v '^build/' \
          | grep -v '^meli/' \
          | grep -v '^\.claude/' \
          | grep -v '^release-' \
          || true
    )

    if [ -z "$CHANGED" ]; then
        echo "Nenhuma alteracao detectada. Use --all para sincronizar tudo."
        exit 0
    fi

    # Detectar arquivos deletados e removê-los do remote
    DELETED=$(git -C "$ROOT_DIR" diff --name-only --diff-filter=D HEAD 2>/dev/null \
        | grep -v '^vendor/' \
        | grep -v '^node_modules/' \
        | grep -v '^e2e/' \
        | grep -v '^build/' \
        | grep -v '^meli/' \
        | grep -v '^\.claude/' \
        | grep -v '^release-' \
        || true)

    if [ -n "$DELETED" ]; then
        while IFS= read -r file; do
            ssh $SSH_OPTS "$REMOTE_USER@$REMOTE_HOST" "rm -f $REMOTE_PATH/$file" 2>/dev/null && echo "  🗑 $file (deletado)"
        done <<< "$DELETED"
    fi

    while IFS= read -r file; do
        [ -f "$ROOT_DIR/$file" ] || continue
        remote_dir="$REMOTE_PATH/$(dirname "$file")"
        ssh $SSH_OPTS "$REMOTE_USER@$REMOTE_HOST" "mkdir -p $remote_dir"
        scp -i "$REMOTE_SSH_KEY" -o StrictHostKeyChecking=accept-new \
            "$ROOT_DIR/$file" \
            "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/$file"
        echo "  ✓ $file"
    done <<< "$CHANGED"
fi

echo ""
echo "✅ Sincronizacao concluida."
echo ""
