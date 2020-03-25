
UPDATE `__prefix__base_question` SET `required` = '1', `onJoin` = 1 WHERE `name` = 'googlemap_location';

UPDATE `__prefix__base_config` SET `value` = '__api_key__' WHERE `name` = 'api_key' AND `key` = 'googlelocation';
UPDATE `__prefix__base_config` SET `value` = '1' WHERE `name` = 'display_map_on_profile_pages' AND `key` = 'googlelocation';
