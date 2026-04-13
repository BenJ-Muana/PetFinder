-- Run this in phpMyAdmin or MySQL CLI to add map support
ALTER TABLE pets
  ADD COLUMN latitude  DECIMAL(10,8) DEFAULT NULL AFTER location,
  ADD COLUMN longitude DECIMAL(11,8) DEFAULT NULL AFTER latitude,
  ADD COLUMN landmark  VARCHAR(255)  DEFAULT NULL AFTER longitude;
