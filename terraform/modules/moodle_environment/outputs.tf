output "alb_dns_name" {
  value = aws_lb.moodle.dns_name
}

output "alb_zone_id" {
  value = aws_lb.moodle.zone_id
}

output "moodle_wwwroot" {
  value = local.moodle_wwwroot
}

output "ecs_cluster_name" {
  value = aws_ecs_cluster.moodle.name
}

output "ecs_service_name" {
  value = aws_ecs_service.moodle.name
}

output "ecs_cron_service_name" {
  value = aws_ecs_service.cron.name
}

output "task_definition_arn" {
  value = aws_ecs_task_definition.moodle.arn
}

output "database_endpoint" {
  value = aws_db_instance.moodle.address
}

output "moodle_secret_arn" {
  value     = aws_secretsmanager_secret.moodle.arn
  sensitive = true
}

output "redis_endpoint" {
  value = aws_elasticache_replication_group.redis.primary_endpoint_address
}

output "efs_file_system_id" {
  value = aws_efs_file_system.moodledata.id
}
