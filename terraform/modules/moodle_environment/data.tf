resource "aws_db_subnet_group" "moodle" {
  name       = "${local.name_prefix}-db-subnets"
  subnet_ids = values(aws_subnet.private)[*].id

  tags = merge(local.common_tags, {
    Name = "${local.name_prefix}-db-subnets"
  })
}

resource "aws_db_instance" "moodle" {
  identifier              = "${local.name_prefix}-postgres"
  engine                  = "postgres"
  engine_version          = "16"
  instance_class          = var.database_instance_class
  allocated_storage       = var.database_allocated_storage
  max_allocated_storage   = var.database_max_allocated_storage
  db_name                 = local.db_name
  username                = local.db_username
  password                = random_password.database.result
  port                    = 5432
  db_subnet_group_name    = aws_db_subnet_group.moodle.name
  vpc_security_group_ids  = [aws_security_group.database.id]
  publicly_accessible     = false
  storage_encrypted       = true
  backup_retention_period = var.database_backup_retention_days
  deletion_protection     = var.database_deletion_protection
  skip_final_snapshot     = true

  tags = merge(local.common_tags, {
    Name = "${local.name_prefix}-postgres"
  })
}

resource "aws_efs_file_system" "moodledata" {
  encrypted        = true
  performance_mode = "generalPurpose"
  throughput_mode  = "elastic"

  tags = merge(local.common_tags, {
    Name = "${local.name_prefix}-moodledata"
  })
}

resource "aws_efs_access_point" "moodledata" {
  file_system_id = aws_efs_file_system.moodledata.id

  posix_user {
    gid = 33
    uid = 33
  }

  root_directory {
    path = "/moodledata"

    creation_info {
      owner_gid   = 33
      owner_uid   = 33
      permissions = "0775"
    }
  }

  tags = merge(local.common_tags, {
    Name = "${local.name_prefix}-moodledata-ap"
  })
}

resource "aws_efs_mount_target" "moodledata" {
  for_each = aws_subnet.private

  file_system_id  = aws_efs_file_system.moodledata.id
  subnet_id       = each.value.id
  security_groups = [aws_security_group.efs.id]
}

resource "aws_elasticache_subnet_group" "redis" {
  name       = "${local.name_prefix}-redis-subnets"
  subnet_ids = values(aws_subnet.private)[*].id
}

resource "aws_elasticache_replication_group" "redis" {
  replication_group_id       = "${local.name_prefix}-redis"
  description                = "Redis cache for ${local.name_prefix}"
  engine                     = "redis"
  engine_version             = "7.1"
  node_type                  = var.redis_node_type
  num_cache_clusters         = var.redis_node_count
  automatic_failover_enabled = var.redis_node_count > 1
  multi_az_enabled           = var.redis_node_count > 1
  at_rest_encryption_enabled = true
  transit_encryption_enabled = true
  subnet_group_name          = aws_elasticache_subnet_group.redis.name
  security_group_ids         = [aws_security_group.redis.id]

  tags = merge(local.common_tags, {
    Name = "${local.name_prefix}-redis"
  })
}

resource "aws_secretsmanager_secret" "moodle" {
  name = "${local.name_prefix}/moodle"

  tags = merge(local.common_tags, {
    Name = "${local.name_prefix}-moodle-secret"
  })
}

resource "aws_secretsmanager_secret_version" "moodle" {
  secret_id = aws_secretsmanager_secret.moodle.id

  secret_string = jsonencode({
    POSTGRES_PASSWORD     = random_password.database.result
    MOODLE_ADMIN_PASSWORD = random_password.admin.result
  })
}
