data "aws_availability_zones" "available" {
  state = "available"
}

locals {
  name_prefix = "${var.project_name}-${var.environment}"

  azs = slice(data.aws_availability_zones.available.names, 0, length(var.public_subnet_cidrs))

  common_tags = {
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }

  db_name          = "moodle"
  db_username      = "moodle"
  container_name   = "moodle"
  container_port   = 80
  moodle_wwwroot   = var.moodle_wwwroot != "" ? var.moodle_wwwroot : "http://${aws_lb.moodle.dns_name}"
  use_ssl_proxy    = startswith(local.moodle_wwwroot, "https://")
  effective_scheme = local.use_ssl_proxy ? "HTTPS" : "HTTP"

  moodle_environment = [
    { name = "MOODLE_DB_TYPE", value = "pgsql" },
    { name = "MOODLE_DB_HOST", value = aws_db_instance.moodle.address },
    { name = "MOODLE_DB_PORT", value = "5432" },
    { name = "MOODLE_DB_PREFIX", value = "mdl_" },
    { name = "MOODLE_WWWROOT", value = local.moodle_wwwroot },
    { name = "MOODLE_DATAROOT", value = "/var/www/moodledata" },
    { name = "MOODLE_REVERSEPROXY", value = "true" },
    { name = "MOODLE_SSLPROXY", value = tostring(local.use_ssl_proxy) },
    { name = "MOODLE_COMPOSER_INSTALL", value = "false" },
    { name = "POSTGRES_DB", value = local.db_name },
    { name = "POSTGRES_USER", value = local.db_username },
    { name = "MOODLE_ADMIN_USER", value = "admin" },
    { name = "MOODLE_ADMIN_EMAIL", value = "admin@example.local" },
    { name = "MOODLE_SITE_FULLNAME", value = "eLearn Mindset ${title(var.environment)}" },
    { name = "MOODLE_SITE_SHORTNAME", value = "elearnmindset-${var.environment}" },
    { name = "MOODLE_REDIS_HOST", value = aws_elasticache_replication_group.redis.primary_endpoint_address },
    { name = "MOODLE_REDIS_PORT", value = "6379" },
    { name = "TZ", value = "UTC" }
  ]

  moodle_secrets = [
    {
      name      = "POSTGRES_PASSWORD"
      valueFrom = "${aws_secretsmanager_secret.moodle.arn}:POSTGRES_PASSWORD::"
    },
    {
      name      = "MOODLE_ADMIN_PASSWORD"
      valueFrom = "${aws_secretsmanager_secret.moodle.arn}:MOODLE_ADMIN_PASSWORD::"
    }
  ]
}

resource "random_password" "database" {
  length           = 32
  special          = true
  override_special = "!#$%&*()-_=+[]{}<>:?"
}

resource "random_password" "admin" {
  length           = 24
  special          = true
  override_special = "!#$%&*()-_=+[]{}<>:?"
}
