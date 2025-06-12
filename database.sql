CREATE DATABASE IF NOT EXISTS wp_calculator;
USE wp_calculator;

CREATE TABLE alternatif (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE kriteria (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    bobot DECIMAL(10,2) NOT NULL,
    tipe ENUM('benefit', 'cost') NOT NULL DEFAULT 'benefit',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE penilaian (
    id INT PRIMARY KEY AUTO_INCREMENT,
    alternatif_id INT NOT NULL,
    kriteria_id INT NOT NULL,
    nilai DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alternatif_id) REFERENCES alternatif(id) ON DELETE CASCADE,
    FOREIGN KEY (kriteria_id) REFERENCES kriteria(id) ON DELETE CASCADE,
    UNIQUE KEY unique_penilaian (alternatif_id, kriteria_id)
);

CREATE TABLE hasil_perhitungan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    alternatif_id INT NOT NULL,
    nilai_s DECIMAL(10,4) NOT NULL,
    nilai_v DECIMAL(10,4) NOT NULL,
    ranking INT NOT NULL,
    tanggal_perhitungan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alternatif_id) REFERENCES alternatif(id) ON DELETE CASCADE
);

-- Insert some sample data
INSERT INTO alternatif (nama) VALUES 
('Alternatif 1'),
('Alternatif 2'),
('Alternatif 3');

INSERT INTO kriteria (nama, bobot, tipe) VALUES 
('Kriteria 1', 0.30, 'benefit'),
('Kriteria 2', 0.25, 'cost'),
('Kriteria 3', 0.45, 'benefit');
