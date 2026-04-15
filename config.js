const dotenv = require('dotenv')
dotenv.config()

module.exports = {
    PORT: process.env.PORT,
    MONGODB_DATABASE: process.env.MONGODB_DATABASE,
    SECRET_PRIVATE_KEY: process.env.SECRET_PRIVATE_KEY,
    MYSQL_DATABASE:process.env.MYSQL_DATABASE,
    MYSQL_HOST:process.env.MYSQL_HOST,
    MYSQL_USER:process.env.MYSQL_USER,
    MYSQL_PASSWORD:process.env.MYSQL_PASSWORD,
}