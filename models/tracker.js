const sequelizeConnection = require('../database/sqlDatabaseConnectivity');
const User = require('./user');
const {
  DataTypes, Sequelize
} = require('sequelize');

const Tracker = sequelizeConnection.define('tracker', {
  id: {
    type: DataTypes.INTEGER,
    autoIncrement: true,
    primaryKey: true
  },
  name: {
    type: DataTypes.JSON,
  }
}, {
  timestamps: true,
  createdAt: 'created_at',
  updatedAt: 'updated_at'
});

Tracker.sync()
  .then(res => {
    console.log("tracker table");
  }).catch(err => {
    console.log(err);
  })

module.exports = Tracker;