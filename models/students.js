const sequelizeConnection = require('../database/sqlDatabaseConnectivity');
const {
    DataTypes
} = require('sequelize');
const User = require('./user');
const Organization = require('./organization');
const Instructor = require('./instructor');

const Students = sequelizeConnection.define('students', {
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
      instructor_id: {
        type: DataTypes.INTEGER,
        references: {
          model: Instructor,
          key: 'id',
        },
      },
      quiz_category_id: {
        type: DataTypes.INTEGER,
    },
    phone: {
        type: DataTypes.TEXT,
    },
    mobile:{
        type: DataTypes.TEXT,
    }
}, {
    timestamps: true,
    createdAt: 'created_at',
    updatedAt: 'updated_at'
})
Students.belongsTo(User, { foreignKey :'user_id' });
Students.belongsTo(Organization, { foreignKey :'organization_id' });
Students.belongsTo(Instructor, { foreignKey :'instructor_id' });
Students.sync()
      .then(res => {
            console.log("Students table");
      }).catch(err => {
            console.log(err);
      })

module.exports = Students;