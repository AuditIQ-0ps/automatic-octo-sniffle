//DOTENV 
require('dotenv').config();

const path = require("path");
const express = require("express");
const port = process.env.PORT;
const cookieParser = require('cookie-parser');
const logger = require('morgan');
const cors = require('cors');

//DATABASE connectivity
require('./database/sqlDatabaseConnectivity');

const app = express();

//user router
const usersRouter = require('./routes/users');

app.set("views", path.join(__dirname, "views"));
app.set("view engine", "hbs");

app.use(logger('dev'));
app.use(express.json());
app.use(express.urlencoded({ extended: false }));
app.use(cookieParser());
app.use("/public", express.static(path.join(__dirname, "/public")));
app.use(cors());

//use user router
app.use('/users', usersRouter);

app.listen(port, () => console.log(`Listening on port ${port}!`));
