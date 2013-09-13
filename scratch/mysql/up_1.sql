CREATE TABLE IF NOT EXISTS schema_version
(
    id INT NOT NULL AUTO_INCREMENT,
    CONSTRAINT schema_version_pk PRIMARY KEY (id),
    delta_id INT NOT NULL,
    direction INT NOT NULL,
    ran_on DATETIME NOT NULL
) ENGINE=INNODB;

INSERT INTO schema_version (
    delta_id,
    direction,
    ran_on)
VALUES (
    1,
    1,
    now());

COMMIT;
