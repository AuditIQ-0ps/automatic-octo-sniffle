const express = require('express');
const router = express.Router();
const {
  signIn,
  signUp,
  logOut,
  insertActivityData,
  activityDetail,
  trackerInsert,
  getTrackerInfo,
  getAllUsers,
  getOrganizationUsers,
  getInstructorUsers,
  particularUser,
  getAllUsersDataByOrganization
} = require('../controllers/userController');

const auth = require('../middleware/auth');

/* GET users listing. */
router.get('/', function (req, res, next) {
  res.send('respond with a resource');
});

router.post('/sign-in', signIn);
router.post('/sign-up', signUp);
router.get('/log-out', auth, logOut);
router.post('/insert-activity-data', auth, insertActivityData);
router.post('/activity-detail', auth, activityDetail);
router.post('/tracker-update', trackerInsert);
router.get('/get-tracker-info', getTrackerInfo);
router.post('/all-users-activity-detail', getAllUsers);
router.post('/all-organization-activity-detail', getOrganizationUsers);
router.post('/all-instructor-activity-detail', getInstructorUsers);
router.post('/user-activity-detail-organization', getAllUsersDataByOrganization);
router.post('/user-activity-detail', particularUser);
module.exports = router;