const sequelizeConnection = require('../database/sqlDatabaseConnectivity');
const User = require('./user');
const {
    DataTypes,
    Sequelize
} = require('sequelize');
const Instructors = require('./instructor');

const Organization = sequelizeConnection.define('organizations', {
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
    name: {
        type: DataTypes.TEXT,
    },
    mobile: {
        type: DataTypes.TEXT,
    },
    phone: {
        type: DataTypes.TEXT,
    },
    description: {
        type: DataTypes.TEXT,
        unique: true
    },
    price: {
        type: DataTypes.INTEGER,
    },
    no_of_instructor: {
        type: DataTypes.INTEGER,
    },
    payment_style_id: {
        type: DataTypes.INTEGER,
    },
    no_of_student: {
        type: DataTypes.INTEGER,
    },
    stripe_customer_id: {
        type: DataTypes.TEXT,
    }
}, {
    timestamps: true,
    createdAt: 'created_at',
    updatedAt: 'updated_at'
});
Organization.belongsTo(User, {
    foreignKey: 'user_id'
});
Instructors.belongsTo(Organization, {
    foreignKey: 'organization_id'}
);

Organization.hasMany(Instructors,{
    foreignKey: 'organization_id'});

Organization.sync()
    .then(res => {
        console.log("Organization table");
    }).catch(err => {
        console.log(err);
    })

module.exports = Organization;