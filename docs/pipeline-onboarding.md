# Pipeline Onboarding And Troubleshooting

This guide is for engineers operating the GitHub Actions and Terraform delivery path.

## Required Setup

| Item | Required Value |
| --- | --- |
| GitHub Environments | `dev`, `stage`, `prod`, `prod-approval` |
| Repository variables | `AWS_ACCOUNT_ID`, `AWS_REGION`, `GHCR_IMAGE_NAME`, `BACKUP_VAULT_NAME`, `BACKUP_ROLE_ARN` |
| Repository secrets | `RENOVATE_TOKEN` |
| AWS bootstrap | `terraform/bootstrap` applied for OIDC roles and Terraform state |
| GHCR | Package exists or first pipeline publish is allowed to create it |
| Local hooks | `pnpm install` |

If GHCR is private, set `container_registry_credentials_secret_arn` in each Terraform environment.

## Pipeline Entry Points

| Workflow | Use |
| --- | --- |
| `Moodle Delivery Pipeline` | Normal build, validation, image publish, Terraform plan/apply, and smoke validation. |
| `Moodle Version Upgrade` | Controlled Moodle tag upgrade with backup, deployment, CLI upgrade, and cron restart. |
| `Server Backup` | Manual RDS snapshot and EFS AWS Backup recovery point. |
| `Server Restore` | Guarded application rollback and restore-point validation. |
| `Infrastructure Drift Detection` | Scheduled or manual refresh-only Terraform drift detection. |
| `Renovate` | Dependency update PR automation. |

## Normal Release Flow

1. Open a PR.
2. Wait for source integrity, static quality, docs, security, image, and local smoke gates.
3. Merge to `main`.
4. The pipeline publishes a GHCR image.
5. Dev and stage deploy according to environment rules.
6. Stage smoke and browser validation pass.
7. Approve `prod-approval`.
8. Prod deploys, services stabilize, and production smoke validation runs.

## Manual Environment Apply

Use `workflow_dispatch` on `Moodle Delivery Pipeline`:

```text
publish_image=true
apply_infrastructure=true
target_environment=dev|stage|prod|all
run_extended_tests=false
```

Use `target_environment=prod` only after stage validation or an approved emergency change.

## Troubleshooting Matrix

| Failure | First Check | Typical Fix |
| --- | --- | --- |
| `source_integrity` | Moodle tag and Docker Compose config | Confirm `MOODLE_VERSION` exists upstream and `docker compose config --quiet` passes. |
| `static_quality` | ShellCheck, YAML, Terraform fmt/validate | Run `bash -n`, `shellcheck`, `yamllint`, and `terraform fmt -recursive terraform`. |
| `documentation_quality` | Broken local Markdown link | Run `./scripts/validate-docs.sh` and fix missing links. |
| `supply_chain_security` | Composer audit or Trivy high/critical finding | Upgrade the affected dependency or add a documented false-positive ignore only with review. |
| Image build | Moodle source or override sync | Run `./scripts/bootstrap-moodle.sh` and `docker compose build`. |
| Local smoke | Moodle install failure | Check `docker compose logs moodle db`, DB readiness, and `.env` values. |
| Terraform plan | OIDC role, backend, or variable issue | Confirm bootstrap was applied and repo variables match the AWS account and region. |
| Terraform apply | AWS service quota or immutable resource update | Review the plan summary and AWS service event. Do not force state changes. |
| ECS stabilization | Container crash or health check failure | Inspect ECS service events and CloudWatch logs for the task. |
| Remote smoke | ALB, DNS, TLS, or Moodle response issue | Check `terraform output moodle_wwwroot`, ALB target health, and application logs. |
| Drift detection | Out-of-band AWS change | Encode intentional changes in Terraform or revert accidental changes in AWS. |

## Rollback Decision Path

| State | Action |
| --- | --- |
| New ECS deployment failed before Moodle CLI upgrade | Roll ECS service back to the previous task definition. |
| Moodle CLI upgrade started or completed | Restore RDS and EFS from matching backup points, then roll ECS back. |
| Only cron is unhealthy | Keep web running, pause cron, inspect logs, and redeploy the cron service after fix. |
| ALB target health is failing | Stop production promotion and inspect ECS task logs before database actions. |

## Local Validation Before Push

```bash
pnpm validate
./scripts/validate-docs.sh
docker compose config --quiet
terraform fmt -check -recursive terraform
docker run --rm -v "$PWD:/repo" -w /repo rhysd/actionlint:latest .github/workflows/*.yml
```

Run the full Docker smoke path when touching Docker, Moodle install scripts, or Terraform container settings:

```bash
cp .env.example .env
./scripts/bootstrap-moodle.sh
docker compose build
docker compose up -d
./scripts/install-site.sh
curl -fsS http://localhost:8080/login/index.php >/tmp/moodle-login.html
```

## Escalation Data To Capture

- Workflow run URL.
- Commit SHA and GHCR image tag.
- Target environment.
- Terraform plan summary.
- ECS service events.
- CloudWatch log group `/ecs/elearn-mindset-<env>`.
- RDS snapshot or PITR timestamp.
- EFS recovery point ARN.
- Exact user-facing symptom and timestamp.
