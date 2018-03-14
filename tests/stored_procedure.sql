/* Procedure structure for procedure `generate_data` */

DELIMITER $$

DROP PROCEDURE IF EXISTS `generate_data`$$

CREATE PROCEDURE `generate_data`(IN quantity INT)
BEGIN
  DECLARE i INT DEFAULT 0;
  TRUNCATE TABLE __template;

  WHILE i < quantity DO
    INSERT INTO __template (`lastmod`,`subject`) VALUES (
      FROM_UNIXTIME(UNIX_TIMESTAMP('2014-01-01 01:00:00')+FLOOR(RAND()*31536000)),
      ROUND(RAND()*100,2)
    );
    SET i = i + 1;
  END WHILE;
END$$

DELIMITER ;
