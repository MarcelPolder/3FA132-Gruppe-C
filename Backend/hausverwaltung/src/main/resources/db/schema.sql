PRAGMA foreign_keys = ON;

CREATE TABLE reading (
    id INTEGER PRIMARY KEY AUTOINCREMENT, comment TEXT, customer_id INTEGER, date_of_reading TEXT, kind_of_meter TEXT, meter_count INTEGER, meter_id TEXT, substitute INTEGER DEFAULT(0), CONSTRAINT reading_customer_fk FOREIGN KEY (customer_id) REFERENCES "customer" (id) ON DELETE SET NULL
);

CREATE TABLE customer (
    id INTEGER PRIMARY KEY AUTOINCREMENT, vorname TEXT, nachname TEXT
);

CREATE TABLE user (
    id INTEGER PRIMARY KEY AUTOINCREMENT, firstname TEXT, lastname TEXT, password TEXT, token TEXT
);