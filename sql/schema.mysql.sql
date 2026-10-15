-- Optional MySQL schema. The app creates these tables itself.
-- Create the database first: CREATE DATABASE 272project CHARACTER SET utf8mb4;
-- Column names that the 2017 queries used are unchanged:
-- products(id, imgUrl, description, name, price, hits)
-- orders(orderid, userid)
-- ordermap(orderid, productid)
-- ratings(productid, stars, timestamp, userId, review)

CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    imgUrl VARCHAR(1024) NOT NULL,
    hits INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    orderid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    userid INT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (userid) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ordermap (
    orderid INT NOT NULL,
    productid INT NOT NULL,
    PRIMARY KEY (orderid, productid),
    FOREIGN KEY (orderid) REFERENCES orders(orderid),
    FOREIGN KEY (productid) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ratings (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    productid INT NOT NULL,
    stars INT NOT NULL,
    `timestamp` DATETIME NOT NULL,
    userId INT NOT NULL,
    review TEXT NOT NULL,
    CHECK (stars BETWEEN 1 AND 5),
    FOREIGN KEY (productid) REFERENCES products(id),
    FOREIGN KEY (userId) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
