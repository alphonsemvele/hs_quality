terraform {
  required_version = ">= 1.6"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.70"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.6"
    }
  }

  # Uncomment and customize when a remote state backend exists.
  # The backend config itself lives outside Terraform-managed state to
  # avoid a chicken-and-egg problem. See backend.tf.example.
  #
  # backend "s3" {
  #   bucket         = "qualite-domicile-tfstate"
  #   key            = "environments/staging/terraform.tfstate"
  #   region         = "eu-west-3"
  #   encrypt        = true
  #   dynamodb_table = "qualite-domicile-tflocks"
  # }
}

provider "aws" {
  region = var.aws_region

  default_tags {
    tags = local.common_tags
  }
}
