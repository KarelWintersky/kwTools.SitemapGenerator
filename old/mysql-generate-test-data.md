# Вызов процедуры

CALL procedure_name();


# 1

CREATE PROCEDURE insertMe()
BEGIN
 DECLARE i BIGINT DEFAULT 1;
 WHILE (i <= 1000) DO
   INSERT INTO mytable values(i);
   SET i=i+1;
 END WHILE;
END;

# 2

CREATE PROCEDURE insertMe()
BEGIN
 DECLARE i BIGINT DEFAULT 1;
 START TRANSACTIOON;
 WHILE (i <= 999999999) DO
   INSERT INTO mytable values(i);
   SET i=i+1;
 END WHILE;
 COMMIT;
END;

# 3

DELIMITER $$
CREATE PROCEDURE generate_data()
BEGIN
  DECLARE i INT DEFAULT 0;
  WHILE i < 1000 DO
    INSERT INTO `data` (`datetime`,`value`,`channel`) VALUES (
      FROM_UNIXTIME(UNIX_TIMESTAMP('2014-01-01 01:00:00')+FLOOR(RAND()*31536000)),
      ROUND(RAND()*100,2),
      1
    );
    SET i = i + 1;
  END WHILE;
END$$
DELIMITER ;

CALL generate_data();

