output "moodle_wwwroot" {
  value = module.moodle.moodle_wwwroot
}

output "alb_dns_name" {
  value = module.moodle.alb_dns_name
}

output "ecs_cluster_name" {
  value = module.moodle.ecs_cluster_name
}

output "ecs_service_name" {
  value = module.moodle.ecs_service_name
}

output "ecs_cron_service_name" {
  value = module.moodle.ecs_cron_service_name
}

output "database_endpoint" {
  value = module.moodle.database_endpoint
}

output "efs_file_system_id" {
  value = module.moodle.efs_file_system_id
}

output "cloudwatch_alarm_names" {
  value = module.moodle.cloudwatch_alarm_names
}

output "route53_record_fqdn" {
  value = try(module.route53[0].record_fqdn, null)
}
