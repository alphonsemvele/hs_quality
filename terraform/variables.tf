variable "aws_region" {
  description = "AWS region. Must be HDS-certified (eu-west-3 Paris)."
  type        = string
  default     = "eu-west-3"
}

variable "project_name" {
  description = "Short identifier used as a prefix on every resource."
  type        = string
  default     = "qualite-domicile"
}

variable "environment" {
  description = "Environment name (staging, production)."
  type        = string

  validation {
    condition     = contains(["staging", "production"], var.environment)
    error_message = "environment must be one of: staging, production"
  }
}

# --- Network ------------------------------------------------------------------

variable "vpc_cidr" {
  description = "CIDR block for the VPC."
  type        = string
  default     = "10.20.0.0/16"
}

variable "public_subnet_cidrs" {
  description = "CIDR blocks for public subnets (one per AZ)."
  type        = list(string)
  default     = ["10.20.1.0/24", "10.20.2.0/24"]
}

variable "private_subnet_cidrs" {
  description = "CIDR blocks for private subnets (one per AZ)."
  type        = list(string)
  default     = ["10.20.11.0/24", "10.20.12.0/24"]
}

variable "database_subnet_cidrs" {
  description = "CIDR blocks for database-only subnets (one per AZ)."
  type        = list(string)
  default     = ["10.20.21.0/24", "10.20.22.0/24"]
}

# --- RDS PostgreSQL -----------------------------------------------------------

variable "db_name" {
  description = "PostgreSQL database name."
  type        = string
  default     = "qualite_domicile"
}

variable "db_username" {
  description = "PostgreSQL master username."
  type        = string
  default     = "qualite_admin"
}

variable "db_password" {
  description = "PostgreSQL master password. Generate a strong one or let Terraform generate via random_password."
  type        = string
  sensitive   = true
}

variable "db_instance_class" {
  description = "RDS instance type. db.t4g.small for staging, db.t4g.medium+ for production."
  type        = string
  default     = "db.t4g.small"
}

variable "db_allocated_storage" {
  description = "Initial storage in GB. Grows via autoscaling up to db_max_allocated_storage."
  type        = number
  default     = 20
}

variable "db_max_allocated_storage" {
  description = "Maximum storage in GB for RDS storage autoscaling."
  type        = number
  default     = 100
}

variable "db_multi_az" {
  description = "Enable multi-AZ standby (production: true; staging: optional)."
  type        = bool
  default     = true
}

variable "db_backup_retention_days" {
  description = "Daily snapshot retention. HDS expects >= 7."
  type        = number
  default     = 7
}

# --- ElastiCache Redis --------------------------------------------------------

variable "redis_node_type" {
  description = "ElastiCache node type. cache.t4g.micro for staging; cache.t4g.small+ for production."
  type        = string
  default     = "cache.t4g.micro"
}

variable "redis_num_cache_clusters" {
  description = "Number of cache clusters (1 primary + replicas)."
  type        = number
  default     = 2
}

variable "redis_engine_version" {
  description = "Redis engine version."
  type        = string
  default     = "7.1"
}

# --- Tagging ------------------------------------------------------------------

variable "extra_tags" {
  description = "Extra tags merged into the default tag set."
  type        = map(string)
  default     = {}
}
