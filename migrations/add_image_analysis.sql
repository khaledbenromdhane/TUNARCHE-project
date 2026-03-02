-- Migration: Add image_analysis column to publication table
-- Run this SQL against your 'artvista' database to enable automatic image analysis storage.

ALTER TABLE `publication`
    ADD COLUMN `image_analysis` LONGTEXT NULL DEFAULT NULL COMMENT 'JSON-encoded image analysis results (dominant_colors, brightness, saturation, style_tags, visual_elements, composition)';
