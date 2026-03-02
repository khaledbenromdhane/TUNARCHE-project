-- Add slug column
ALTER TABLE publication ADD COLUMN slug VARCHAR(255) UNIQUE;

-- Populate existing slugs based on titre
-- Note: This is a simple conversion for MySQL. 
-- For more complex slugification, we might need a PHP script.
-- However, for simple cases, we can try to replace spaces with hyphens.
UPDATE publication SET slug = LOWER(REPLACE(titre, ' ', '-')) WHERE slug IS NULL;
