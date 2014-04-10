CREATE TABLE `prt_tnk1` (
  `qod` varchar(255) DEFAULT NULL,
  `sug` varchar(15) DEFAULT NULL,
  `tvnit` varchar(15) DEFAULT NULL,
  `kotrt` varchar(255) DEFAULT NULL,
  `ktovt` varchar(255) DEFAULT NULL,
  `m` varchar(255) DEFAULT NULL,
  `l` varchar(255) DEFAULT NULL,
  `tarik_hosfa` timestamp
) ENGINE=InnoDB CHARACTER SET utf8;

SET character_set_database=utf8;

LOAD DATA LOCAL INFILE '$BACKUP_FILEROOT/prt_tnk1.txt'  INTO TABLE prt_tnk1 (qod,sug,tvnit,kotrt,ktovt,m,l,tarik_hosfa);
