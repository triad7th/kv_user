CREATE TABLE users ( 
    kv_order INT UNSIGNED NOT NULL, 
    id CHAR(30),
    first_name CHAR(30),
    middle_name CHAR(30),
    last_name CHAR(30),
    password CHAR(30),
    email VARCHAR(128),
    address_1 VARCHAR(256),
    address_2 VARCHAR(256),
    zipcode CHAR(30),
    country CHAR(30),
    last_access VARCHAR(128),
    log_state VARCHAR(128),
    PRIMARY KEY(kv_order)
)