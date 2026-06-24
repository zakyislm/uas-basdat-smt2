


SET FOREIGN_KEY_CHECKS = 0;


INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `is_verified`, `role`) VALUES 
(1, 'zaky', 'zakislam238@gmail.com', '$2y$10$YSewRit9iZrQCSg6Ciz71ualV5gFV8c7bqR32phe15AmCN/tkYg3.', 1, 'owner'),
(2, 'Joko (User)', 'user@gmail.com', '$2y$10$eTRsaGXo7UfTXmImDfGMSed92bRieteYTkUx91z6ndxsiOCLqiYTm', 1, 'user'),
(3, 'Budi (Admin)', 'admin@gmail.com', '$2y$10$eTRsaGXo7UfTXmImDfGMSed92bRieteYTkUx91z6ndxsiOCLqiYTm', 1, 'admin'),
(4, 'Tono (Owner)', 'owner@gmail.com', '$2y$10$eTRsaGXo7UfTXmImDfGMSed92bRieteYTkUx91z6ndxsiOCLqiYTm', 1, 'owner');


INSERT INTO `motorcycles` (`id`, `make`, `model`, `year`, `price`, `description`, `stock`, `mileage`) VALUES
("1","Honda","CBR250RR","2024","65000000","Motor sport 250cc dengan fitur quickshifter.","4","17753"),
("2","Yamaha","NMAX 155","2023","31000000","Skuter matik bongsor nyaman untuk harian.","12","22865"),
("3","Kawasaki","Ninja ZX-25R","2024","105000000","Motor sport 4 silinder 250cc suara gahar.","2","11069"),
("4","Suzuki","GSX-R150","2022","34000000","Motor sport entry level dengan keyless ignition.","8","11749"),
("5","Kawasaki","Versys-X 250","2018","116000000","Motor Kawasaki Versys-X 250 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","50","539"),
("6","Kawasaki","Z1000","2020","169000000","Motor Kawasaki Z1000 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.","7","17450"),
("7","Ducati","Scrambler","2024","292000000","Motor Ducati Scrambler keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.","5","10632"),
("8","BMW","S1000RR","2018","693000000","Motor BMW S1000RR keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","43","812"),
("9","Suzuki","GSX-R150","2022","51000000","Motor Suzuki GSX-R150 keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","14","22163"),
("10","Kawasaki","Ninja 250","2021","200000000","Motor Kawasaki Ninja 250 keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.","44","8378"),
("11","Vespa","LX 125","2024","43000000","Motor Vespa LX 125 keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.","45","402"),
("12","BMW","G310R","2023","854000000","Motor BMW G310R keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","41","1877"),
("13","Triumph","Trident 660","2019","782000000","Motor Triumph Trident 660 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.","31","8181"),
("14","Royal Enfield","Continental GT 650","2024","91000000","Motor Royal Enfield Continental GT 650 keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.","5","10273"),
("15","BMW","R1250GS","2023","771000000","Motor BMW R1250GS keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","30","1824"),
("16","Yamaha","Fazzio","2019","50000000","Motor Yamaha Fazzio keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.","33","3302"),
("17","Kawasaki","ZX-25R","2021","196000000","Motor Kawasaki ZX-25R keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.","18","11038"),
("18","Royal Enfield","Classic 350","2021","81000000","Motor Royal Enfield Classic 350 keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.","45","20285"),
("19","Kawasaki","Z1000","2024","60000000","Motor Kawasaki Z1000 keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.","19","18312"),
("20","Yamaha","NMAX 155","2018","55000000","Motor Yamaha NMAX 155 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","42","5705"),
("21","Kawasaki","W175","2023","82000000","Motor Kawasaki W175 keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","14","23588"),
("22","BMW","S1000RR","2022","352000000","Motor BMW S1000RR keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","17","826"),
("23","Triumph","Tiger 900","2020","379000000","Motor Triumph Tiger 900 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.","31","8369"),
("24","BMW","G310R","2019","991000000","Motor BMW G310R keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.","48","14366"),
("25","Suzuki","GSX-R150","2020","58000000","Motor Suzuki GSX-R150 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.","10","21722"),
("26","Vespa","Sprint","2020","21000000","Motor Vespa Sprint keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.","3","15512"),
("27","Royal Enfield","Himalayan","2022","159000000","Motor Royal Enfield Himalayan keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","50","12398"),
("28","Vespa","Sprint","2018","55000000","Motor Vespa Sprint keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","5","15451"),
("29","Kawasaki","Z1000","2022","106000000","Motor Kawasaki Z1000 keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","13","15063"),
("30","Triumph","Tiger 900","2020","792000000","Motor Triumph Tiger 900 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.","30","3961"),
("31","Vespa","Primavera","2024","52000000","Motor Vespa Primavera keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.","33","24619"),
("32","Royal Enfield","Interceptor 650","2019","160000000","Motor Royal Enfield Interceptor 650 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.","14","11211"),
("33","Honda","Vario 160","2024","60000000","Motor Honda Vario 160 keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.","0","7199"),
("34","Kawasaki","Versys-X 250","2020","193000000","Motor Kawasaki Versys-X 250 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.","31","2363"),
("35","Royal Enfield","Continental GT 650","2022","68000000","Motor Royal Enfield Continental GT 650 keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","49","15219"),
("36","Kawasaki","W175","2023","199000000","Motor Kawasaki W175 keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","4","19007"),
("37","KTM","RC 390","2019","52000000","Motor KTM RC 390 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.","10","24378"),
("38","Honda","PCX 160","2018","48000000","Motor Honda PCX 160 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","16","14870"),
("39","Ducati","Scrambler","2018","319000000","Motor Ducati Scrambler keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","32","1214"),
("40","Honda","CBR250RR","2022","33000000","Motor Honda CBR250RR keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","11","11463"),
("41","Honda","ADV 160","2018","49000000","Motor Honda ADV 160 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","28","3674"),
("42","KTM","Duke 390","2023","95000000","Motor KTM Duke 390 keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","39","8980"),
("43","Triumph","Bonneville T100","2023","887000000","Motor Triumph Bonneville T100 keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","16","8877"),
("44","Honda","Beat Street","2023","47000000","Motor Honda Beat Street keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","32","17447"),
("45","Honda","CBR250RR","2021","29000000","Motor Honda CBR250RR keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.","28","10603"),
("46","Suzuki","Nex II","2018","50000000","Motor Suzuki Nex II keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","28","676"),
("47","Kawasaki","Ninja 250","2018","52000000","Motor Kawasaki Ninja 250 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","29","21571"),
("48","KTM","Duke 390","2019","67000000","Motor KTM Duke 390 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.","47","5828"),
("49","Kawasaki","ZX-25R","2023","113000000","Motor Kawasaki ZX-25R keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","0","14426"),
("50","BMW","S1000RR","2021","923000000","Motor BMW S1000RR keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.","4","4649"),
("51","Ducati","Scrambler","2022","621000000","Motor Ducati Scrambler keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","47","4968"),
("52","BMW","G310R","2024","909000000","Motor BMW G310R keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.","23","10891"),
("53","Triumph","Speed Triple","2021","526000000","Motor Triumph Speed Triple keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.","42","14553"),
("54","KTM","Adventure 390","2018","169000000","Motor KTM Adventure 390 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","48","15091"),
("55","Kawasaki","KLX 150","2024","140000000","Motor Kawasaki KLX 150 keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.","48","6796"),
("56","Yamaha","R15","2019","31000000","Motor Yamaha R15 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.","23","13706"),
("57","Royal Enfield","Interceptor 650","2018","40000000","Motor Royal Enfield Interceptor 650 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","3","23145"),
("58","KTM","Duke 250","2024","67000000","Motor KTM Duke 250 keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.","29","24608"),
("59","Suzuki","V-Strom 250","2020","36000000","Motor Suzuki V-Strom 250 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.","1","3605"),
("60","Honda","Vario 160","2024","45000000","Motor Honda Vario 160 keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.","41","19202"),
("61","Yamaha","Fazzio","2022","29000000","Motor Yamaha Fazzio keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","27","10197"),
("62","BMW","G310GS","2022","423000000","Motor BMW G310GS keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","24","18377"),
("63","BMW","G310GS","2018","970000000","Motor BMW G310GS keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","7","11294"),
("64","Ducati","Monster","2018","983000000","Motor Ducati Monster keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","26","1342"),
("65","Kawasaki","KLX 150","2020","189000000","Motor Kawasaki KLX 150 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.","34","22829"),
("66","Triumph","Trident 660","2023","271000000","Motor Triumph Trident 660 keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","44","10118"),
("67","BMW","R1250GS","2023","832000000","Motor BMW R1250GS keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","50","7102"),
("68","Honda","CBR250RR","2023","16000000","Motor Honda CBR250RR keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","16","5157"),
("69","Triumph","Tiger 900","2019","595000000","Motor Triumph Tiger 900 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.","25","4481"),
("70","Yamaha","R25","2021","25000000","Motor Yamaha R25 keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.","44","6934"),
("71","BMW","G310R","2020","356000000","Motor BMW G310R keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.","29","21227"),
("72","Royal Enfield","Meteor 350","2020","119000000","Motor Royal Enfield Meteor 350 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.","8","10334"),
("73","Ducati","Monster","2019","912000000","Motor Ducati Monster keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.","50","12989"),
("74","Ducati","Panigale V4","2018","976000000","Motor Ducati Panigale V4 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","0","8945"),
("75","BMW","S1000RR","2019","646000000","Motor BMW S1000RR keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.","41","5759"),
("76","Royal Enfield","Himalayan","2021","200000000","Motor Royal Enfield Himalayan keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.","20","1961"),
("77","Suzuki","V-Strom 250","2023","23000000","Motor Suzuki V-Strom 250 keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","5","17526"),
("78","Yamaha","WR155R","2018","39000000","Motor Yamaha WR155R keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","38","6750"),
("79","KTM","Adventure 390","2021","163000000","Motor KTM Adventure 390 keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.","4","6170"),
("80","Suzuki","Burgman Street","2024","32000000","Motor Suzuki Burgman Street keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.","47","10603"),
("81","Ducati","Scrambler","2020","479000000","Motor Ducati Scrambler keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.","35","9504"),
("82","Kawasaki","ZX-25R","2022","49000000","Motor Kawasaki ZX-25R keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","29","15713"),
("83","BMW","S1000RR","2024","371000000","Motor BMW S1000RR keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.","32","53"),
("84","Suzuki","Burgman Street","2023","39000000","Motor Suzuki Burgman Street keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","39","3125"),
("85","BMW","G310GS","2018","263000000","Motor BMW G310GS keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","37","15469"),
("86","Royal Enfield","Meteor 350","2019","186000000","Motor Royal Enfield Meteor 350 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.","13","17968"),
("87","Ducati","Multistrada V4","2021","444000000","Motor Ducati Multistrada V4 keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.","31","18435"),
("88","Royal Enfield","Interceptor 650","2020","122000000","Motor Royal Enfield Interceptor 650 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.","37","13273"),
("89","Triumph","Speed Triple","2023","407000000","Motor Triumph Speed Triple keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","37","11060"),
("90","Honda","PCX 160","2019","35000000","Motor Honda PCX 160 keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.","4","15481"),
("91","KTM","Duke 390","2018","168000000","Motor KTM Duke 390 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","34","19224"),
("92","Kawasaki","ZX-25R","2024","76000000","Motor Kawasaki ZX-25R keluaran tahun 2024. Kondisi mulus, performa maksimal, siap pakai.","6","24677"),
("93","Kawasaki","ZX-25R","2023","120000000","Motor Kawasaki ZX-25R keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","42","15715"),
("94","BMW","R1250GS","2023","330000000","Motor BMW R1250GS keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","35","4545"),
("95","Royal Enfield","Classic 350","2018","100000000","Motor Royal Enfield Classic 350 keluaran tahun 2018. Kondisi mulus, performa maksimal, siap pakai.","28","581"),
("96","KTM","Duke 390","2022","43000000","Motor KTM Duke 390 keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","45","14268"),
("97","Royal Enfield","Meteor 350","2020","110000000","Motor Royal Enfield Meteor 350 keluaran tahun 2020. Kondisi mulus, performa maksimal, siap pakai.","3","19598"),
("98","Vespa","Sprint","2019","35000000","Motor Vespa Sprint keluaran tahun 2019. Kondisi mulus, performa maksimal, siap pakai.","34","5185"),
("99","Kawasaki","Z1000","2023","115000000","Motor Kawasaki Z1000 keluaran tahun 2023. Kondisi mulus, performa maksimal, siap pakai.","46","17133"),
("100","Suzuki","GSX-R150","2022","29000000","Motor Suzuki GSX-R150 keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","21","20109"),
("101","Vespa","Primavera","2022","35000000","Motor Vespa Primavera keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","0","24148"),
("102","Suzuki","Nex II","2022","53000000","Motor Suzuki Nex II keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","27","10412"),
("103","Honda","ADV 160","2022","45000000","Motor Honda ADV 160 keluaran tahun 2022. Kondisi mulus, performa maksimal, siap pakai.","24","4616"),
("104","KTM","Duke 250","2021","102000000","Motor KTM Duke 250 keluaran tahun 2021. Kondisi mulus, performa maksimal, siap pakai.","32","16846");


INSERT IGNORE INTO `brands` (`id`, `name`, `origin_country`) VALUES 
(1, 'Honda', 'Japan'), 
(2, 'Yamaha', 'Japan'), 
(3, 'Kawasaki', 'Japan'),
(4, 'Suzuki', 'Japan'),
(5, 'Ducati', 'Italy'),
(6, 'BMW', 'Germany'),
(7, 'Triumph', 'UK'),
(8, 'Royal Enfield', 'UK/India'),
(9, 'Vespa', 'Italy'),
(10, 'KTM', 'Austria');

INSERT IGNORE INTO `categories` (`id`, `name`, `description`) VALUES 
(1, 'Sport', 'Sport bikes'), 
(2, 'Matic', 'Automatic scooters'),
(3, 'Adventure/Touring', 'Adventure dual-purpose motorcycles'),
(4, 'Retro/Classic', 'Classic design styling and cruisers');

INSERT IGNORE INTO `colors` (`id`, `color_name`, `hex_code`) VALUES 
(1, 'Red', '#FF0000'), 
(2, 'Black', '#000000'),
(3, 'Blue', '#0000FF'),
(4, 'Green', '#00FF00'),
(5, 'White', '#FFFFFF');

INSERT IGNORE INTO `engine_types` (`id`, `type_name`, `description`) VALUES 
(1, '125cc', '125cc Single Cylinder'),
(2, '150cc', '150cc Single Cylinder'), 
(3, '250cc', '250cc Multi-cylinder'),
(4, '650cc+', '650cc and above twin/inline/V engines');

INSERT IGNORE INTO `payment_methods` (`id`, `method_name`) VALUES 
(1, 'Transfer Bank (Manual Verification)'), 
(2, 'Kredit Leasing'),
(3, 'Tunai Keras (Cash Outright)');

INSERT IGNORE INTO `provinces` (`id`, `name`) VALUES 
(1, 'DKI Jakarta'), 
(2, 'Jawa Barat'),
(3, 'Banten'),
(4, 'Jawa Tengah'),
(5, 'Jawa Timur');

INSERT IGNORE INTO `cities` (`id`, `province_id`, `name`) VALUES 
(1, 1, 'Jakarta Selatan'), 
(2, 2, 'Bandung'),
(3, 3, 'Tangerang'),
(4, 4, 'Semarang'),
(5, 5, 'Surabaya');

INSERT IGNORE INTO `dealers` (`id`, `city_id`, `name`, `address`) VALUES 
(1, 1, 'MotoInfy Pusat', 'Jl. Jend Sudirman No. 12, Jakarta'),
(2, 2, 'MotoInfy Bandung', 'Jl. Asia Afrika No. 45, Bandung');

INSERT IGNORE INTO `discounts` (`id`, `code`, `percentage`, `valid_until`, `usage_limit`, `used_count`, `is_active`) VALUES 
(1, 'PROMO10', 10, '2027-12-31 23:59:59', 100, 0, 1), 
(2, 'EXPIRED5', 5, '2020-01-01 00:00:00', 50, 50, 0);

INSERT IGNORE INTO `shipping_methods` (`id`, `method_name`, `base_cost`) VALUES 
(1, 'Towing Resmi', 500000), 
(2, 'Ambil di Dealer', 0);

INSERT IGNORE INTO `tax_rates` (`id`, `province_id`, `percentage`) VALUES 
(1, 1, 11), 
(2, 2, 11),
(3, 3, 11);


INSERT INTO `notifications` (`id`, `user_id`, `target_role`, `message`, `link`, `icon`, `color`, `bg`, `is_read`) VALUES
(2, NULL, 'admin', 'Pesanan baru: #TX-3', 'admin?page=transactions', 'receipt_long', 'text-blue-500', 'bg-blue-100', 0),
(3, NULL, 'admin', 'Konfirmasi pembayaran: #TX-3', 'admin?page=transactions', 'payments', 'text-amber-500', 'bg-amber-100', 0);

UPDATE `motorcycles` SET `category_id` = 1 WHERE `model` LIKE '%CBR%' OR `model` LIKE '%Ninja%' OR `model` LIKE '%ZX-%' OR `model` LIKE '%GSX%' OR `model` LIKE '%YZF%' OR `model` LIKE '%R15%' OR `model` LIKE '%R25%' OR `model` LIKE '%Monster%' OR `model` LIKE '%Panigale%' OR `model` LIKE '%S1000RR%';
UPDATE `motorcycles` SET `category_id` = 2 WHERE `category_id` IS NULL;
UPDATE `motorcycles` SET `engine_type_id` = 2 WHERE `model` LIKE '%250%' OR `model` LIKE '%300%' OR `model` LIKE '%400%' OR `model` LIKE '%Monster%' OR `model` LIKE '%Panigale%' OR `model` LIKE '%S1000RR%' OR `model` LIKE '%ZX-25R%' OR `model` LIKE '%ZX25R%';
UPDATE `motorcycles` SET `engine_type_id` = 1 WHERE `engine_type_id` IS NULL;
UPDATE `motorcycles` SET `color_id` = 1 WHERE `id` % 2 = 0;
UPDATE `motorcycles` SET `color_id` = 2 WHERE `color_id` IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
