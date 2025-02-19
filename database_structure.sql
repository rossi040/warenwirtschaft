-- Datenbank erstellen
CREATE DATABASE IF NOT EXISTS invoicing_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE invoicing_system;

-- Benutzer-Tabelle
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Einstellungen-Tabelle
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(100) NOT NULL,
    company_street VARCHAR(100),
    company_house_number VARCHAR(10),
    company_zip VARCHAR(10),
    company_city VARCHAR(100),
    company_phone VARCHAR(50),
    company_email VARCHAR(100),
    company_website VARCHAR(100),
    company_tax_number VARCHAR(50),
    company_vat_id VARCHAR(50),
    default_vat DECIMAL(5,2) DEFAULT 19.00,
    bank_name VARCHAR(100),
    bank_iban VARCHAR(50),
    bank_bic VARCHAR(20)
);

-- Hersteller-Tabelle
CREATE TABLE manufacturers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    salutation VARCHAR(20),
    company_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(50),
    email VARCHAR(100),
    website VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Artikel-Tabelle
CREATE TABLE articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    article_number VARCHAR(50) UNIQUE,
    description VARCHAR(255) NOT NULL,
    manufacturer_id INT,
    purchase_price DECIMAL(10,2),
    selling_price DECIMAL(10,2),
    stock INT DEFAULT 0,
    orderable BOOLEAN DEFAULT TRUE,
    detailed_description TEXT,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id) ON DELETE SET NULL
);

-- Kunden-Tabelle
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    salutation VARCHAR(20),
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    company_name VARCHAR(100),
    street VARCHAR(100),
    house_number VARCHAR(10),
    zip_code VARCHAR(10),
    city VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Rechnungen-Tabelle
CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    vat_rate DECIMAL(5,2) NOT NULL,
    vat_amount DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('draft', 'sent', 'paid', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Rechnungspositionen-Tabelle
CREATE TABLE invoice_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    article_id INT NOT NULL,
    quantity INT NOT NULL,
    price_per_unit DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id)
);

-- Lieferscheine-Tabelle
CREATE TABLE delivery_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    delivery_note_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    delivery_date DATE NOT NULL,
    status ENUM('draft', 'sent', 'delivered', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Lieferscheinpositionen-Tabelle
CREATE TABLE delivery_note_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    delivery_note_id INT NOT NULL,
    article_id INT NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (delivery_note_id) REFERENCES delivery_notes(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id)
);

-- Trigger für automatische Artikelnummer
DELIMITER //
CREATE TRIGGER before_article_insert 
BEFORE INSERT ON articles
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    SET next_id = (SELECT IFNULL(MAX(id), 0) + 1 FROM articles);
    SET NEW.article_number = CONCAT('ART', LPAD(next_id, 6, '0'));
END;//
DELIMITER ;

-- Trigger für automatische Rechnungsnummer
DELIMITER //
CREATE TRIGGER before_invoice_insert 
BEFORE INSERT ON invoices
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    SET next_id = (SELECT IFNULL(MAX(id), 0) + 1 FROM invoices);
    SET NEW.invoice_number = CONCAT('RE', DATE_FORMAT(CURRENT_DATE, '%Y%m'), LPAD(next_id, 4, '0'));
END;//
DELIMITER ;

-- Trigger für automatische Lieferscheinnummer
DELIMITER //
CREATE TRIGGER before_delivery_note_insert 
BEFORE INSERT ON delivery_notes
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    SET next_id = (SELECT IFNULL(MAX(id), 0) + 1 FROM delivery_notes);
    SET NEW.delivery_note_number = CONCAT('LS', DATE_FORMAT(CURRENT_DATE, '%Y%m'), LPAD(next_id, 4, '0'));
END;//
DELIMITER ;

-- Index für häufige Suchanfragen
CREATE INDEX idx_article_search ON articles(description);
CREATE INDEX idx_customer_search ON customers(last_name, first_name, company_name);
CREATE INDEX idx_invoice_date ON invoices(invoice_date);
CREATE INDEX idx_delivery_date ON delivery_notes(delivery_date);