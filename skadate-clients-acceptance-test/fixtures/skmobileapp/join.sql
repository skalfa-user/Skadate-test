
UPDATE `__prefix__base_question` SET `required` = '1', `onJoin` = 1 WHERE `name` = 'googlemap_location';

UPDATE `__prefix__base_config` SET `value` = '__api_key__' WHERE `name` = 'google_map_api_key' AND `key` = 'skmobileapp';
