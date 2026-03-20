---
paths:
  - "Console/**/*.php"
---

# Console Commands

Magento CLI commands exposed via `bin/magento`.

## Structure

- `Console/Command/Adminstrative/` -- Administrative CLI commands
- `Console/Command/Notification/` -- Notification-related CLI commands

## Conventions

- Commands extend `\Symfony\Component\Console\Command\Command`
- Register commands in `etc/di.xml` under `Magento\Framework\Console\CommandListInterface`
- Define `configure()` for name, description, and arguments/options
- Implement logic in `execute(InputInterface $input, OutputInterface $output)`

## Rules

- Commands must return `Cli::RETURN_SUCCESS` (0) or `Cli::RETURN_FAILURE` (1)
- Use `$output->writeln()` for user-facing messages
- Never call the ObjectManager directly -- inject dependencies via constructor
- Long-running commands should show progress with `\Symfony\Component\Console\Helper\ProgressBar`
