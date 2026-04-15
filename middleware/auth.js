const { verifyToken } = require('../helper/sqlHelper');
const auth = async (req, res, next) => {
    try {
        var token = req.headers.authorization?.split(" ")[1];
        let decodeData;
        if (token) {
            decodeData = await verifyToken(token);
            req.user = decodeData.verify.user_id;
            next();
        }
        else {
            res.status(401).json({
                status: 2,
                message: "You are not Authorized"
            })
        }
    }
    catch (error) {
        res.status(401).json({
            status: 2,
            message: "You are not Authorized"
        })
    }
}
module.exports = auth;