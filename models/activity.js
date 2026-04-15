const sequelizeConnection = require('../database/sqlDatabaseConnectivity');
const User = require('./user');
const {
  DataTypes, Sequelize
} = require('sequelize');

const Activity = sequelizeConnection.define('activities', {
  id: {
    type: DataTypes.INTEGER,
    autoIncrement: true,
    primaryKey: true
  },
  userId: {
    type: DataTypes.INTEGER,
    references: {
      model: User,
      key: 'id',
    },
  },
  date: {
    type: DataTypes.DATEONLY,
  },
  login: {
    type: DataTypes.DATE,
  },
  logout: {
    type: DataTypes.DATE,
  },
  total_time: {
    type: DataTypes.INTEGER,
  },
  activity: {type:DataTypes.JSON}
}, {
  timestamps: true,
  createdAt: 'created_at',
  updatedAt: 'updated_at'
});

Activity.sync()
  .then(res => {
    console.log("Activity table");
  }).catch(err => {
    console.log(err);
  })

module.exports = Activity;