const mysql = require("mysql2");
const Sequelize = require("sequelize");
const {
    MYSQL_DATABASE,
    MYSQL_HOST,
    MYSQL_USER,
    MYSQL_PASSWORD
} = require('../config');

const connection = mysql.createConnection({
    host:MYSQL_HOST,
    user: MYSQL_USER,
    password: MYSQL_PASSWORD,
});

connection.query(
    `CREATE DATABASE IF NOT EXISTS ${MYSQL_DATABASE}`,
    function (err, results) {
        if (!err) {
            console.log("Database is done.");
        } else {
            console.log("Error to create database ---->> ", err);
        }
    }
);

connection.end();

const sequelize = new Sequelize(MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD, {
    host: MYSQL_HOST,
    dialect: "mysql"
})

sequelize.authenticate().then(result => {
    console.log('Connection has been established successfully.');
}).catch(error => {
    console.error('Unable to connect to the database:', error);
})

module.exports = sequelize