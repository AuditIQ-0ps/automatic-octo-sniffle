const sequelizeConnection = require('../database/sqlDatabaseConnectivity');
const {
    DataTypes
} = require('sequelize');

const User = sequelizeConnection.define('user', {
    id: {
        type: DataTypes.INTEGER,
        autoIncrement: true,
        primaryKey: true
    },
    first_name: {
        type: DataTypes.TEXT,
    },
    last_name: {
        type: DataTypes.TEXT,
    },
    email: {
        type: DataTypes.STRING,
        unique: true
    },
    email_verified_At: {
        type: DataTypes.DATE,
    },
    phone: {
        type: DataTypes.TEXT,
    },
    role_id:{
        type: DataTypes.INTEGER,
    },
    password: {
        type: DataTypes.TEXT
    },
    remember_token: {
        type: DataTypes.TEXT
    },
    status:{
        type: DataTypes.INTEGER,
    }
}, {
    timestamps: true,
    createdAt: 'created_at',
    updatedAt: 'updated_at'
})


User.sync()
      .then(res => {
            console.log("User table");
      }).catch(err => {
            console.log(err);
      })

module.exports = User;