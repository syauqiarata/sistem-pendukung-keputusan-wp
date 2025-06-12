-- Create database
CREATE DATABASE IF NOT EXISTS spk_wp;
USE spk_wp;

-- Create tables
CREATE TABLE alternatif (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE kriteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    bobot DECIMAL(10,2) NOT NULL,
    tipe ENUM('benefit', 'cost') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE penilaian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alternatif_id INT NOT NULL,
    kriteria_id INT NOT NULL,
    nilai DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alternatif_id) REFERENCES alternatif(id) ON DELETE CASCADE,
    FOREIGN KEY (kriteria_id) REFERENCES kriteria(id) ON DELETE CASCADE
);

CREATE TABLE hasil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alternatif_id INT NOT NULL,
    nilai_s DECIMAL(10,4) NOT NULL,
    nilai_v DECIMAL(10,4) NOT NULL,
    ranking INT NOT NULL,
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alternatif_id) REFERENCES alternatif(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO alternatif (nama) VALUES 
('Alternatif 1'),
('Alternatif 2'),
('Alternatif 3');

INSERT INTO kriteria (nama, bobot, tipe) VALUES 
('Harga', 0.30, 'cost'),
('Kualitas', 0.40, 'benefit'),
('Pelayanan', 0.30, 'benefit');
