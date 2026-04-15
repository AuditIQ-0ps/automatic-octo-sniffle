const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');

const COMMON_ERROR = "Error, Please try again...!";

const QUERY = {
    find: "find",
    findOne: "findOne",
    create: "create",
    findById: "findById",
    findOneAndDelete: "findOneAndDelete",
    findOneAndUpdate: "findOneAndUpdate",
    upsert: 'upsert',
    countDocuments: "countDocuments",
}

const HTTPStatus = {
    OK_STATUS: 200,
    CREATED: 201,
    BAD_REQUEST: 400,
    UNAUTHORIZED: 401,
    NOT_FOUND: 404,
    MEDIA_ERROR_STATUS: 415,
    VALIDATION_FAILURE_STATUS: 417,
    DATABASE_ERROR_STATUS: 422,
    INTERNAL_SERVER_ERROR: 500,
}

const {
    find,
    findOne,
    create,
    findById,
    findOneAndDelete,
    findOneAndUpdate,
    upsert,
    countDocuments
} = QUERY

module.exports = {
    QUERY,
    HTTPStatus,
    COMMON_ERROR,

    // all query functionality
    commonQuery: async (model, query, data = {}, update = {}, select = null, populate = null, perPage = 0, page = 0) => {
        try {
            let res;
            switch (query) {
                case find:
                    const findData = {
                        where: data
                    }
                    if (perPage != 0 && page != 0) {
                        findData.limit = perPage
                        findData.offset = perPage * (page - 1)
                    }

                    if (populate) {
                        findData.include = populate
                    }
                    if (select) {
                        findData.order = [select];
                    }
                    res = await model.findAll(findData)
                    res = res.map(data => data.dataValues)
                    // .sort(update).select(select).populate(populate).lean();
                    break;
                case findOne:
                    res = await model.findOne({
                        where: data
                    });
                    break;
                case create:
                    res = await model.create(data);
                    break;
                case findOneAndUpdate:
                    res = await model.update(update, {
                        where: data
                    })
                    break;
                case upsert:
                    res = await model.findOneAndUpdate(data, update, {
                        upsert: true,
                        new: true
                    });
                    break;
                case findOneAndDelete:
                    res = await model.destroy({
                        where: data
                    });
                    break;
                case countDocuments:
                    res = await model.count({
                        where: data
                    });
                    break;
            }

            if (!res || !data) {
                return {
                    status: 2,
                    message: "Error, please try again."
                }
            } else {
                return {
                    status: 1,
                    data: res
                }
            }
        } catch (error) {
            return {
                status: 1,
                data: error
            }
        }
    },

    //Generate token using JWT
    generateToken: async (data) => {
        try {
            let token = jwt.sign(data, process.env.SECRET_PRIVATE_KEY)
            return {
                status: 1,
                token
            };
        } catch (err) {
            return {
                status: 0,
                message: "token does not generated"
            };
        }
    },

    //verify JWT token
    verifyToken: async (token) => {
        try {
            let verify = jwt.verify(token, process.env.SECRET_PRIVATE_KEY)
            return {
                status: 1,
                verify
            };
        } catch (err) {
            return {
                status: 0,
                message: "token does not verified"
            };
        }
    },

    //create hash of password using bcryptjs
    createBcryptPassword: async (password) => {
        try {
            let hash_password = await bcrypt.hash(password, 10);
            return hash_password;
        } catch (err) {
            return {
                status: 0,
                message: "Error, please try again"
            };
        }
    },

    //compare password
    checkBcryptPassword: async (password, savedPassword) => {
        try {
            let is_match = await bcrypt.compare(password, savedPassword);
            if (!is_match) {
                return {
                    status: 2,
                    message: 'password do not match.'
                };
            } else {
                return {
                    status: 1,
                    message: 'Welcome to Project.'
                };
            };
        } catch (err) {
            return {
                status: 0,
                message: "password do not match"
            };
        }
    },

}