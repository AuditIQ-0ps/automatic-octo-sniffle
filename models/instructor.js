const sequelizeConnection = require('../database/sqlDatabaseConnectivity');
const {
    DataTypes
} = require('sequelize');
const Organization = require('./organization');
const User = require('./user');

const Instructors = sequelizeConnection.define('instructors', {
    id: {
        type: DataTypes.INTEGER,
        autoIncrement: true,
        primaryKey: true
      },
      user_id: {
        type: DataTypes.INTEGER,
        references: {
          model: User,
          key: 'id',
        },
      },
      organization_id: {
        type: DataTypes.INTEGER,
        references: {
          model: Organization,
          key: 'id',
        },
      },
      birth_date: {
        type: DataTypes.DATE,
      },
      age: {
        type: DataTypes.INTEGER,
    },
    phone: {
        type: DataTypes.TEXT,
    },
    qualification: {
        type: DataTypes.TEXT,
        unique: true
    },
    experience: {
        type: DataTypes.TEXT,
      }
}, {
    timestamps: true,
    createdAt: 'created_at',
    updatedAt: 'updated_at'
})
Instructors.belongsTo(User, { foreignKey :'user_id' });
// Instructors.belongsTo(Organization, { foreignKey :'organization_id' });
Instructors.sync()
      .then(res => {
            console.log("Instructors table");
      }).catch(err => {
            console.log(err);
      })

module.exports = Instructors;