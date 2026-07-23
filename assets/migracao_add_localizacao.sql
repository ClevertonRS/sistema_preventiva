ALTER TABLE preventivas_rede
ADD COLUMN latitude DECIMAL(10,8) DEFAULT NULL AFTER chave_combinacao,
ADD COLUMN longitude DECIMAL(11,8) DEFAULT NULL AFTER latitude;