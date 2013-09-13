-- Note: there is no DROP TABLE schema_version statement here on purpose.

INSERT INTO schema_version (
    delta_id,
    direction,
    ran_on)
VALUES (
    1,
    0,
    now());

COMMIT;
