-- SQL Server script to drop all tables
DECLARE @sql NVARCHAR(MAX) = '';

SELECT @sql += 'DROP TABLE [' + TABLE_NAME + ']; '
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'dbo'
AND TABLE_TYPE = 'BASE TABLE'
AND TABLE_NAME NOT IN ('dtproperties', 'sysdiagrams');

EXECUTE sp_executesql @sql;
