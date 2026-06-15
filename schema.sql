    -- CREATE DATABASE IF NOT EXISTS dealer_db;
    -- use dealer_db;
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        is_verified BOOLEAN DEFAULT FALSE,
        role VARCHAR(20) DEFAULT 'user' -- Nilai: 'user', 'admin', 'owner'
    );

    CREATE TABLE motorcycles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        make VARCHAR(100) NOT NULL,
        model VARCHAR(100) NOT NULL,
        year INT NOT NULL,
        price FLOAT NOT NULL,
        description TEXT,
        stock INT NOT NULL DEFAULT 0,
        mileage INT DEFAULT 0
    );

    CREATE TABLE transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        motorcycle_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        type enum('buy','booking') NOT NULL DEFAULT 'buy', -- 'booking' atau 'buy'
        payment_status enum('unpaid','pending_verification','paid','refunded') DEFAULT 'unpaid', -- 'unpaid', 'pending_verification', 'paid', 'refunded'
        transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        status enum('pending','confirmed','cancelled') DEFAULT 'pending',
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (motorcycle_id) REFERENCES motorcycles(id) ON DELETE CASCADE
    );

    CREATE TABLE logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action_detail TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        target_role VARCHAR(20) NULL,
        message TEXT NOT NULL,
        link VARCHAR(255),
        icon VARCHAR(50),
        color VARCHAR(50) DEFAULT 'text-slate-500',
        bg VARCHAR(50) DEFAULT 'bg-slate-100',
        is_read BOOLEAN DEFAULT FALSE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE carts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        motorcycle_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (motorcycle_id) REFERENCES motorcycles(id) ON DELETE CASCADE
    );

    -- ==============================================
    -- DUMMY DATA / SEEDER
    -- (Semua password akun di bawah ini adalah: 123456)
    -- ==============================================

    -- Insert 3 Dummy Users (User, Admin, Owner)
    INSERT INTO users (username, email, password_hash, is_verified, role) VALUES 
    ('Joko (User)', 'user@gmail.com', '$2y$10$eTRsaGXo7UfTXmImDfGMSed92bRieteYTkUx91z6ndxsiOCLqiYTm', 1, 'user'),
    ('Budi (Admin)', 'admin@gmail.com', '$2y$10$eTRsaGXo7UfTXmImDfGMSed92bRieteYTkUx91z6ndxsiOCLqiYTm', 1, 'admin'),
    ('Tono (Owner)', 'owner@gmail.com', '$2y$10$eTRsaGXo7UfTXmImDfGMSed92bRieteYTkUx91z6ndxsiOCLqiYTm', 1, 'owner');

    -- Insert Dummy Motorcycles
    INSERT INTO motorcycles (make, model, year, price, description, stock, mileage) VALUES 
    ('Honda', 'CBR250RR', 2024, 65000000, 'Motor sport 250cc dengan fitur quickshifter.', 5, 1200),
    ('Yamaha', 'NMAX 155', 2023, 31000000, 'Skuter matik bongsor nyaman untuk harian.', 12, 5400),
    ('Kawasaki', 'Ninja ZX-25R', 2024, 105000000, 'Motor sport 4 silinder 250cc suara gahar.', 2, 800),
    ('Suzuki', 'GSX-R150', 2022, 35000000, 'Motor sport 150cc yang ringan dan kencang.', 8, 15000),
    ('Honda', 'Beat Street', 2023, 18500000, 'Skuter matik lincah untuk perkotaan.', 20, 8500),
    ('Yamaha', 'XSR 155', 2023, 38000000, 'Motor bergaya retro klasik dengan mesin modern.', 4, 3200),
    ('Vespa', 'Primavera 150', 2022, 50000000, 'Skuter klasik premium ala Italia.', 3, 11000),
    ('Kawasaki', 'W175', 2021, 33000000, 'Motor retro karburator yang asyik dimodifikasi.', 7, 21000);


    -- 100 DUMMY MOTORCYCLES
    INSERT INTO motorcycles (make, model, year, price, description, stock) VALUES 
    ('Kawasaki', 'Versys-X 250', 2018, 116000000, 'Motor Kawasaki Versys-X 250 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 50),
    ('Kawasaki', 'Z1000', 2020, 169000000, 'Motor Kawasaki Z1000 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.', 7),
    ('Ducati', 'Scrambler', 2024, 292000000, 'Motor Ducati Scrambler keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.', 5),
    ('BMW', 'S1000RR', 2018, 693000000, 'Motor BMW S1000RR keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 43),
    ('Suzuki', 'GSX-R150', 2022, 51000000, 'Motor Suzuki GSX-R150 keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 14),
    ('Kawasaki', 'Ninja 250', 2021, 200000000, 'Motor Kawasaki Ninja 250 keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.', 44),
    ('Vespa', 'LX 125', 2024, 43000000, 'Motor Vespa LX 125 keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.', 45),
    ('BMW', 'G310R', 2023, 854000000, 'Motor BMW G310R keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 41),
    ('Triumph', 'Trident 660', 2019, 782000000, 'Motor Triumph Trident 660 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.', 31),
    ('Royal Enfield', 'Continental GT 650', 2024, 91000000, 'Motor Royal Enfield Continental GT 650 keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.', 5),
    ('BMW', 'R1250GS', 2023, 771000000, 'Motor BMW R1250GS keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 30),
    ('Yamaha', 'Fazzio', 2019, 50000000, 'Motor Yamaha Fazzio keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.', 33),
    ('Kawasaki', 'ZX-25R', 2021, 196000000, 'Motor Kawasaki ZX-25R keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.', 18),
    ('Royal Enfield', 'Classic 350', 2021, 81000000, 'Motor Royal Enfield Classic 350 keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.', 45),
    ('Kawasaki', 'Z1000', 2024, 60000000, 'Motor Kawasaki Z1000 keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.', 19),
    ('Yamaha', 'NMAX 155', 2018, 55000000, 'Motor Yamaha NMAX 155 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 42),
    ('Kawasaki', 'W175', 2023, 82000000, 'Motor Kawasaki W175 keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 14),
    ('BMW', 'S1000RR', 2022, 352000000, 'Motor BMW S1000RR keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 17),
    ('Triumph', 'Tiger 900', 2020, 379000000, 'Motor Triumph Tiger 900 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.', 31),
    ('BMW', 'G310R', 2019, 991000000, 'Motor BMW G310R keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.', 48),
    ('Suzuki', 'GSX-R150', 2020, 58000000, 'Motor Suzuki GSX-R150 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.', 10),
    ('Vespa', 'Sprint', 2020, 21000000, 'Motor Vespa Sprint keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.', 3),
    ('Royal Enfield', 'Himalayan', 2022, 159000000, 'Motor Royal Enfield Himalayan keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 50),
    ('Vespa', 'Sprint', 2018, 55000000, 'Motor Vespa Sprint keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 5),
    ('Kawasaki', 'Z1000', 2022, 106000000, 'Motor Kawasaki Z1000 keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 13),
    ('Triumph', 'Tiger 900', 2020, 792000000, 'Motor Triumph Tiger 900 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.', 30),
    ('Vespa', 'Primavera', 2024, 52000000, 'Motor Vespa Primavera keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.', 33),
    ('Royal Enfield', 'Interceptor 650', 2019, 160000000, 'Motor Royal Enfield Interceptor 650 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.', 14),
    ('Honda', 'Vario 160', 2024, 60000000, 'Motor Honda Vario 160 keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.', 0),
    ('Kawasaki', 'Versys-X 250', 2020, 193000000, 'Motor Kawasaki Versys-X 250 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.', 31),
    ('Royal Enfield', 'Continental GT 650', 2022, 68000000, 'Motor Royal Enfield Continental GT 650 keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 49),
    ('Kawasaki', 'W175', 2023, 199000000, 'Motor Kawasaki W175 keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 4),
    ('KTM', 'RC 390', 2019, 52000000, 'Motor KTM RC 390 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.', 10),
    ('Honda', 'PCX 160', 2018, 48000000, 'Motor Honda PCX 160 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 16),
    ('Ducati', 'Scrambler', 2018, 319000000, 'Motor Ducati Scrambler keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 32),
    ('Honda', 'CBR250RR', 2022, 33000000, 'Motor Honda CBR250RR keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 11),
    ('Honda', 'ADV 160', 2018, 49000000, 'Motor Honda ADV 160 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 28),
    ('KTM', 'Duke 390', 2023, 95000000, 'Motor KTM Duke 390 keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 39),
    ('Triumph', 'Bonneville T100', 2023, 887000000, 'Motor Triumph Bonneville T100 keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 16),
    ('Honda', 'Beat Street', 2023, 47000000, 'Motor Honda Beat Street keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 32),
    ('Honda', 'CBR250RR', 2021, 29000000, 'Motor Honda CBR250RR keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.', 28),
    ('Suzuki', 'Nex II', 2018, 50000000, 'Motor Suzuki Nex II keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 28),
    ('Kawasaki', 'Ninja 250', 2018, 52000000, 'Motor Kawasaki Ninja 250 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 29),
    ('KTM', 'Duke 390', 2019, 67000000, 'Motor KTM Duke 390 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.', 47),
    ('Kawasaki', 'ZX-25R', 2023, 113000000, 'Motor Kawasaki ZX-25R keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 0),
    ('BMW', 'S1000RR', 2021, 923000000, 'Motor BMW S1000RR keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.', 4),
    ('Ducati', 'Scrambler', 2022, 621000000, 'Motor Ducati Scrambler keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 47),
    ('BMW', 'G310R', 2024, 909000000, 'Motor BMW G310R keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.', 23),
    ('Triumph', 'Speed Triple', 2021, 526000000, 'Motor Triumph Speed Triple keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.', 42),
    ('KTM', 'Adventure 390', 2018, 169000000, 'Motor KTM Adventure 390 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 48),
    ('Kawasaki', 'KLX 150', 2024, 140000000, 'Motor Kawasaki KLX 150 keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.', 48),
    ('Yamaha', 'R15', 2019, 31000000, 'Motor Yamaha R15 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.', 23),
    ('Royal Enfield', 'Interceptor 650', 2018, 40000000, 'Motor Royal Enfield Interceptor 650 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 3),
    ('KTM', 'Duke 250', 2024, 67000000, 'Motor KTM Duke 250 keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.', 29),
    ('Suzuki', 'V-Strom 250', 2020, 36000000, 'Motor Suzuki V-Strom 250 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.', 1),
    ('Honda', 'Vario 160', 2024, 45000000, 'Motor Honda Vario 160 keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.', 41),
    ('Yamaha', 'Fazzio', 2022, 29000000, 'Motor Yamaha Fazzio keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 27),
    ('BMW', 'G310GS', 2022, 423000000, 'Motor BMW G310GS keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 24),
    ('BMW', 'G310GS', 2018, 970000000, 'Motor BMW G310GS keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 7),
    ('Ducati', 'Monster', 2018, 983000000, 'Motor Ducati Monster keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 26),
    ('Kawasaki', 'KLX 150', 2020, 189000000, 'Motor Kawasaki KLX 150 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.', 34),
    ('Triumph', 'Trident 660', 2023, 271000000, 'Motor Triumph Trident 660 keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 44),
    ('BMW', 'R1250GS', 2023, 832000000, 'Motor BMW R1250GS keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 50),
    ('Honda', 'CBR250RR', 2023, 16000000, 'Motor Honda CBR250RR keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 16),
    ('Triumph', 'Tiger 900', 2019, 595000000, 'Motor Triumph Tiger 900 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.', 25),
    ('Yamaha', 'R25', 2021, 25000000, 'Motor Yamaha R25 keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.', 44),
    ('BMW', 'G310R', 2020, 356000000, 'Motor BMW G310R keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.', 29),
    ('Royal Enfield', 'Meteor 350', 2020, 119000000, 'Motor Royal Enfield Meteor 350 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.', 8),
    ('Ducati', 'Monster', 2019, 912000000, 'Motor Ducati Monster keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.', 50),
    ('Ducati', 'Panigale V4', 2018, 976000000, 'Motor Ducati Panigale V4 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 0),
    ('BMW', 'S1000RR', 2019, 646000000, 'Motor BMW S1000RR keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.', 41),
    ('Royal Enfield', 'Himalayan', 2021, 200000000, 'Motor Royal Enfield Himalayan keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.', 20),
    ('Suzuki', 'V-Strom 250', 2023, 23000000, 'Motor Suzuki V-Strom 250 keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 5),
    ('Yamaha', 'WR155R', 2018, 39000000, 'Motor Yamaha WR155R keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 38),
    ('KTM', 'Adventure 390', 2021, 163000000, 'Motor KTM Adventure 390 keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.', 4),
    ('Suzuki', 'Burgman Street', 2024, 32000000, 'Motor Suzuki Burgman Street keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.', 47),
    ('Ducati', 'Scrambler', 2020, 479000000, 'Motor Ducati Scrambler keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.', 35),
    ('Kawasaki', 'ZX-25R', 2022, 49000000, 'Motor Kawasaki ZX-25R keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 29),
    ('BMW', 'S1000RR', 2024, 371000000, 'Motor BMW S1000RR keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.', 32),
    ('Suzuki', 'Burgman Street', 2023, 39000000, 'Motor Suzuki Burgman Street keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 39),
    ('BMW', 'G310GS', 2018, 263000000, 'Motor BMW G310GS keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 37),
    ('Royal Enfield', 'Meteor 350', 2019, 186000000, 'Motor Royal Enfield Meteor 350 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.', 13),
    ('Ducati', 'Multistrada V4', 2021, 444000000, 'Motor Ducati Multistrada V4 keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.', 31),
    ('Royal Enfield', 'Interceptor 650', 2020, 122000000, 'Motor Royal Enfield Interceptor 650 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.', 37),
    ('Triumph', 'Speed Triple', 2023, 407000000, 'Motor Triumph Speed Triple keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 37),
    ('Honda', 'PCX 160', 2019, 35000000, 'Motor Honda PCX 160 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.', 4),
    ('KTM', 'Duke 390', 2018, 168000000, 'Motor KTM Duke 390 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 34),
    ('Kawasaki', 'ZX-25R', 2024, 76000000, 'Motor Kawasaki ZX-25R keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.', 6),
    ('Kawasaki', 'ZX-25R', 2023, 120000000, 'Motor Kawasaki ZX-25R keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 42),
    ('BMW', 'R1250GS', 2023, 330000000, 'Motor BMW R1250GS keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 35),
    ('Royal Enfield', 'Classic 350', 2018, 100000000, 'Motor Royal Enfield Classic 350 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.', 28),
    ('KTM', 'Duke 390', 2022, 43000000, 'Motor KTM Duke 390 keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 45),
    ('Royal Enfield', 'Meteor 350', 2020, 110000000, 'Motor Royal Enfield Meteor 350 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.', 3),
    ('Vespa', 'Sprint', 2019, 35000000, 'Motor Vespa Sprint keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.', 34),
    ('Kawasaki', 'Z1000', 2023, 115000000, 'Motor Kawasaki Z1000 keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.', 46),
    ('Suzuki', 'GSX-R150', 2022, 29000000, 'Motor Suzuki GSX-R150 keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 21),
    ('Vespa', 'Primavera', 2022, 35000000, 'Motor Vespa Primavera keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 0),
    ('Suzuki', 'Nex II', 2022, 53000000, 'Motor Suzuki Nex II keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 27),
    ('Honda', 'ADV 160', 2022, 45000000, 'Motor Honda ADV 160 keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.', 24),
    ('KTM', 'Duke 250', 2021, 102000000, 'Motor KTM Duke 250 keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.', 32);

CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action_detail TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
