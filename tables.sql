-- gts.Unigis definition

CREATE TABLE `Unigis` (
  `posicionId` int(11) NOT NULL AUTO_INCREMENT,
  `vehiculoId` varchar(24) NOT NULL DEFAULT '',
  `velocidad` int(10) DEFAULT NULL,
  `satelites` smallint(5) DEFAULT NULL,
  `rumbo` double DEFAULT NULL,
  `latitud` double DEFAULT NULL,
  `longitud` double DEFAULT NULL,
  `altitud` double DEFAULT NULL,
  `gpsDateTime` varchar(50) DEFAULT NULL,
  `statusCode` int(11) DEFAULT NULL,
  `ignition` int(11) DEFAULT NULL,
  `odometro` double DEFAULT NULL,
  `horometro` double DEFAULT NULL,
  `nivelBateria` double DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`posicionId`,`vehiculoId`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- Trigger

IF (instr(@agente, 'UNI') > 0) THEN
	set @newEngineHours = new.engineHours;
	set @newBatteryLevel = new.batteryLevel;
	set @newSatelliteCount = new.satelliteCount;
    set @newOdometerKM = round(new.odometerKM,2);
    set @gpsDateTime = date_format(from_unixtime(@newTimestamp+18000), '%Y-%m-%dT%H:%i:%s');
	INSERT INTO Unigis (vehiculoId, velocidad, satelites, rumbo, latitud, longitud, altitud, gpsDateTime, statusCode, ignition, odometro, horometro, nivelBateria, estado)
	VALUES (@newLicensePlate, round(@newSpeed,0),@newSatelliteCount,round(@newHeading,0),format(@newLatitude,5), format(@newLongitude,5),round(@newAltitude,0),@gpsDateTime,@newStatusCode,1,round(@newOdometerKM,0),round(@newEngineHours,0),round(@newBatteryLevel,0),'Nuevo');
END IF;