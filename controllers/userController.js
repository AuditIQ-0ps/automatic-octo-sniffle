const userModel = require('../models/user');
const activityModel = require('../models/activity');
const organizationModel = require('../models/organization');
const instructorModel = require('../models/instructor');
const studentModel = require('../models/students');
const { QUERY, COMMON_ERROR, commonQuery, generateToken ,checkBcryptPassword} = require('../helper/sqlHelper');
const { find, create, findOne, upsert, findOneAndUpdate } = QUERY;
const moment = require('moment');
const TrackerModel = require('../models/tracker');
const {Op}=require('sequelize');
const User = require('../models/user');
const Organization = require('../models/organization');
const Instructors = require('../models/instructor');

//time format function

function format(time) {
  // Hours, minutes and seconds
  var hrs = ~~(time / 3600);
  var mins = ~~((time % 3600) / 60);
  var secs = ~~time % 60;

  // Output like "1:01" or "4:03:59" or "123:03:59"
  var res = "";
  if (hrs > 0) {
      res += "" + hrs + " hr " + (mins < 10 ? "0" : "");
  }
  if (mins > 0) {
    
    res += "" + mins + ( mins > 1 ? " mins " : " min ")  + (secs < 10 ? "0" : "");
  }
  if (hrs < 1) {
    
    res += "" + secs +  ( secs > 1 ? " seconds " : " second ");
  }
  
  return res;
}

module.exports = {
 
  // user sign-in api function
  signIn: async (req, res) => {
    try {
      const { email, password } = req.body;
      const login = await commonQuery(userModel, findOne, { email });
      if (login.status == 1) {
        const matchPassword = await checkBcryptPassword(password, login?.data?.password)
        if(matchPassword.status == 1){
          var newToken = await generateToken({ user_id: login.data.id })
          var token = newToken?.token;

          const updateData = await commonQuery(userModel, findOneAndUpdate, {_id:login?.data?._id},{token})
          if(updateData.status==1){
            res.status(200).json({ status: 1, message: 'Log in successfully...!', token:token });
          } else {
            res.status(400).json({ status: 0, message: COMMON_ERROR });
          }
        }
        else {
          res.status(400).json({ status: 0, message:"Password does not match"});
        }
      } else {
        res.status(400).json({ status: 0, message: COMMON_ERROR });
      }
    } catch (error) {
      console.log('error', error);
      res.status(400).json({ status: 0, message: COMMON_ERROR });
    }
  },

  // user sign-up api function
  signUp: async (req, res) => {
    try {
      const { email, password, first_name } = req.body;
      const existUser = await commonQuery(userModel, findOne, { email });
      if (existUser.status != 1) {
        const createUser = await commonQuery(userModel, create, {
          email,
          password,
          first_name,
        });
        if (createUser.status == 1) {
          res.status(200).json({ status: 1, message: 'sign-up successfully' });
        } else {
          res.status(400).json({ status: 0, message: COMMON_ERROR });
        }
      } else {
        res
          .status(400)
          .json({ status: 0, message: 'This user is already exist' });
      }
    } catch (error) {
      console.log('error', error);
      res.status(400).json({ status: 0, message: COMMON_ERROR });
    }
  },

  // user logout api function
  logOut: async (req, res) => {
    try {
      const existUser = await commonQuery(userModel, findOne, { id:req?.user });
      if (existUser.status == 1) {
        const date = moment().format('YYYY-MM-DD');
        const id = existUser?.data?.id;
        const logout= await commonQuery(activityModel,findOneAndUpdate, {userId:id, date:date, logout:null},{logout:new Date()});
        if(logout.status ==1){
          res.status(200).json({ status: 1, message: 'Log out successfully...!' });
        }
        else{
          res.status(400).json({ status: 0, message: COMMON_ERROR });
        }
      } else {
        res.status(400).json({ status: 0, message: COMMON_ERROR });
      }
    } catch (error) {
      console.log('error', error);
      res.status(400).json({ status: 0, message: COMMON_ERROR });
    }
  },

  // insert user all activity data into database
  insertActivityData :async(req,res)=>{
    try{
      const {data}=req.body;
      const date = moment(new Date()).format('YYYY-MM-DD');
      const id = req.user;
      const findExistData = await commonQuery(activityModel, findOne, {
        userId: id,
        date: date,
        logout:null
      });
      if (findExistData.status == 1 && (findExistData?.data?.logout == null)) {
        const createData = await commonQuery(
          activityModel,
          findOneAndUpdate,
          { userId: id, date: date, logout:null },
          { activity: data }
        );

      } else {
        const createData = await commonQuery(activityModel, create, {
          userId: id,
          date: date,
          login: new Date(),
          logout:null,
          activity: data,
        });
      }
      return res.json({status:1, message:"Data added."})
    }
    catch (error) {
      console.log('error', error);
      res.status(400).json({ status: 0, message: COMMON_ERROR });
    }
  },

  // get user's all activity data
  activityDetail : async(req,res)=>{
    try {
      const {dateString}=req.body;
      const findActivity = await commonQuery(activityModel,find, {userId:req?.user});
      const findTrackerName = await commonQuery(TrackerModel, find, {});
      const trackerData = JSON.parse(findTrackerName?.data[0]?.name)
      var findData;
      if(dateString == 'yesterday'){
        findData = await commonQuery(activityModel,find, {userId:req?.user , date: moment().add(-1, 'days').format('YYYY-MM-DD')},{},['userId','ASC']);
      }
      else if(dateString == 'lastWeek'){
        const todayDate = moment().format('YYYY-MM-DD')
        const weekDate = moment().add(-1, 'week').format('YYYY-MM-DD')
        findData = await commonQuery(activityModel,find, {userId:req?.user , date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
      }
      else{
        const date = moment().format('YYYY-MM-DD')
        findData = await commonQuery(activityModel,find, {userId:req?.user , date:date},{},['userId','ASC']);
        findData.data[0].newDate = new Date();
      }

      await Promise.all(findData?.data.map((e)=>{
        e.activity =JSON.parse(e?.activity?.toLowerCase());
        e.item = []
        e.activity = e?.activity.filter((act)=>{
          trackerData.map((a)=>{
            if((act.title).match(a.toLowerCase())){
              (e.item).push(act);
              return e
            }
          })
        })
        e.totalTime = (e.item).reduce((previousValue, currentValue) => previousValue + currentValue.total,0,);
        return e;
      }))

      if (findData.status == 1) {
        res.status(200).json({ status: 1, message: "User's activity detail" , data:findData.data});
      } else {
        res.status(400).json({ status: 0, message: COMMON_ERROR });
      }
    } catch (error) {
      console.log('error', error);
      res.status(400).json({ status: 0, message: COMMON_ERROR });
    }
  },

  // which activity admin want to track
  trackerInsert : async(req,res)=>{
    try {
      const {name}=req.body;
      var createData;
      const findData = await commonQuery(TrackerModel, find, {});
      if(findData?.status == 1 && findData?.data?.length > 0){
        createData = await commonQuery(TrackerModel, findOneAndUpdate,{id:findData?.data[0]?.id},{name});
      }
      else{
        createData = await commonQuery(TrackerModel, create,{name});
      }
      if(createData.status == 1){
        res.status(200).json({status:1, message:"Tracking App list is updated."})
      }
      else{
        res.status(400).json({ status: 0, message: COMMON_ERROR });
      }

    } catch (error) {
      console.log('error', error);
      res.status(400).json({ status: 0, message: COMMON_ERROR });
    }
  },

  // tracking activity list
  getTrackerInfo:async(req,res)=>{
    try {
      const findData = await commonQuery(TrackerModel, find, {});
      if(findData.data.length > 0){
        findData.data[0].name = JSON.parse(findData?.data[0]?.name)
      }
      if(findData.status == 1){
        if(findData.data.length > 0){
        res.status(200).json({status:1, message:"Tracking App list.", data : findData?.data[0]?.name})
        }
        else{
        res.status(200).json({status:1, message:"Tracking App list.", data : []})
        }
      }
      else{
        res.status(400).json({ status: 0, message: COMMON_ERROR });
      }
    } catch (error) {
      console.log('error', error);
      res.status(400).json({ status: 0, message: COMMON_ERROR });
    }
  },

  // useless
  // get all user activity data according organization
  getAllUsers:async(req,res)=>{
    try {
      const {dateString}=req.body;
      const to = req?.body?.to;
      const from = req?.body?.from;
      const findAllUser = await commonQuery(userModel, find, {[Op.or]:[{role_id:2},{role_id:3},{role_id:4}]},{},['first_name','ASC']);
      const findTrackerName = await commonQuery(TrackerModel, find, {});
      const trackerData = JSON.parse(findTrackerName?.data[0]?.name)

      var findUserActivityData = [];

      await Promise.all(findAllUser?.data?.map( async (data,i) => {
        if (to && from) {
          const todayDate = moment(to).format('YYYY-MM-DD')
          const weekDate = moment(from).format('YYYY-MM-DD')
          findData = await commonQuery(activityModel,find, {userId:data?.id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
        
          let role;
          if (data?.role_id === 2) {
            role = "Organization"
          }
          if (data?.role_id === 3) {
            role = "Instructor"
          }
          if (data?.role_id === 4) {
            role = "Student"
          }
          const userObject = {
            name: `${data?.first_name} ${data?.last_name}`,
            id: data?.id,
            role_id: role,
            activityData:findData?.data
          }
          findUserActivityData.push(userObject)
        } else {
        if(dateString == 'month'){
          const todayDate = moment().format('YYYY-MM-DD')
          const weekDate = moment().startOf('month').format('YYYY-MM-DD');
          findData = await commonQuery(activityModel,find, {userId:data?.id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
          
          let role;

          if (data?.role_id === 2) {
            role = "Organization"
          }
          if (data?.role_id === 3) {
            role = "Instructor"
          }
          if (data?.role_id === 4) {
            role = "Student"
          }
          const userObject = {
            name: `${data?.first_name} ${data?.last_name}`,
            id: data?.id,
            role_id: role,
            activityData:findData?.data
          }
          findUserActivityData.push(userObject)
        }
        else if(dateString == 'week'){
          const todayDate = moment().format('YYYY-MM-DD')
          const weekDate = moment().startOf('week').format('YYYY-MM-DD')
          findData = await commonQuery(activityModel,find, {userId:data?.id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
        
          let role
          if (data?.role_id === 2) {
            role = "Organization"
          }
          if (data?.role_id === 3) {
            role = "Instructor"
          }
          if (data?.role_id === 4) {
            role = "Student"
          }
          
          const userObject = {
            name: `${data?.first_name} ${data?.last_name}`,
            id: data?.id,
            role_id: role,
            activityData:findData?.data
          }
          findUserActivityData.push(userObject)
        
        }
        else if(dateString == 'today'){
          const date = moment().format('YYYY-MM-DD')
          findData = await commonQuery(activityModel,find, {userId:data?.id,date:date},{},['userId','ASC']);
        
          let role
          if (data?.role_id === 2) {
            role = "Organization"
          }
          if (data?.role_id === 3) {
            role = "Instructor"
          }
          if (data?.role_id === 4) {
            role = "Student"
          } 
          const userObject = {
            name: `${data?.first_name} ${data?.last_name}`,
            id: data?.id,
            role_id: role,
            activityData:findData?.data
          }
          findUserActivityData.push(userObject)
        } else {
          const date = moment().format('YYYY-MM-DD')
          findData = await commonQuery(activityModel,find, {userId:data?.id},{},['userId','ASC']);
        
          let role
          if (data?.role_id === 2) {
            role = "Organization"
          }
          if (data?.role_id === 3) {
            role = "Instructor"
          }
          if (data?.role_id === 4) {
            role = "Student"
          } 
          const userObject = {
            name: `${data?.first_name} ${data?.last_name}`,
            id: data?.id,
            role_id: role,
            activityData:findData?.data
          }
          findUserActivityData.push(userObject)
        }
      }
      }))


     const newData =  findUserActivityData?.map((data => {
      return {
        name: data?.name,
        role_id:data?.role_id,
        id: data?.id,
        user_activity : data?.activityData?.map((d) => {
          let newActivity = JSON.parse(d?.activity)
          let forced_time = 0
          let total_time = 0
          newActivity?.map((e) => {
            total_time = total_time + e?.total
            if (e?.title?.includes("Notepad") || e?.title?.includes("Notepad++")) {
              forced_time = forced_time + e?.total
            }
          })
          return {
            activity:{
              login:d?.login,
              logout:d?.logout,
              time_count:{forced_time,total_time}
            }
          }
        })
      }
      }))

      let newUserActivity =  newData?.map((data) => {
        let new_forced_time = 0
        let new_total_time = 0
        data?.user_activity?.map((d) => {
          new_forced_time = new_forced_time + d?.activity?.time_count?.forced_time
          new_total_time = new_total_time + d?.activity?.time_count?.total_time
        })

        return {
          name:data?.name,
          role:data?.role_id,
          id: data?.id,
          // total_time:moment.utc(Number(new_total_time.toFixed(2)) * 1000).format("HH:mm:ss"),
          total_time:format(Number(new_total_time.toFixed(2))),
          // salesforce_time:moment.utc(Number(new_forced_time.toFixed(2)) * 1000).format("HH:mm:ss"),
          salesforce_time:format(Number(new_forced_time.toFixed(2))),
          last_logout: data?.user_activity[0]?.activity?.logout ? data?.user_activity[0]?.activity?.logout : null,
          last_login: data?.user_activity[0]?.activity?.login ?  data?.user_activity[0]?.activity?.login : null,
        }
      })
    //  const newActivityUser = newUserActivity?.sort((a,b) =>  a?.name - b?.name)
     const newActivityUser = newUserActivity?.sort((a,b) => a.id > b.id ? 1 : -1)

        res.status(200).json({ status: 1, message: "User's activity detail" , data:newActivityUser});
    } catch (error) {
      console.log('error', error);
      res.status(400).json({ status: 0, message: COMMON_ERROR });
    }
  },

  // get all user activity data according organization
  getAllUsersDataByOrganization:async(req,res)=>{
    try {

        const {dateString}=req.body;
        const to = req?.body?.to
        const from = req?.body?.from

        const organization = await commonQuery(organizationModel, find,{},{},'',[userModel]);
        const instructors = await commonQuery(instructorModel, find,{},{},'',[userModel,organizationModel]);
        const students = await commonQuery(studentModel, find,{},{},'',[userModel,organizationModel]);

        let userArray = []
        await Promise.all(organization?.data?.map((data) => {

        const userObject = {
            id:data?.user?.id,
            first_name:data?.user?.first_name,
            last_name:data?.user?.last_name,
            role_id: data?.user?.role_id,
            organization_name:data?.name
          }
          userArray.push(userObject);
        }))

        await Promise.all(students?.data?.map((data) => {
          const userObject = {
            id:data?.user?.id,
            first_name:data?.user?.first_name,
            last_name:data?.user?.last_name,
            role_id: data?.user?.role_id,
            organization_name:data?.organization?.name
          }
          userArray.push(userObject);
        }))

        await Promise.all(instructors?.data?.map((data) => {
          const userObject = {
            id:data?.user?.id,
            first_name:data?.user?.first_name,
            last_name:data?.user?.last_name,
            role_id: data?.user?.role_id,
            organization_name:data?.organization?.name
          }
          userArray.push(userObject);
        }))
          
        var findUserActivityData = [];

        await Promise.all(userArray?.map( async (data,i) => {
          if (to && from) {
            const todayDate = moment(to).format('YYYY-MM-DD')
            const weekDate = moment(from).format('YYYY-MM-DD')
            findData = await commonQuery(activityModel,find, {userId:data?.id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
    
            let role;
            if (data?.role_id === 2) {
              role = "Organization"
            }
            if (data?.role_id === 3) {
              role = "Instructor"
            }
            if (data?.role_id === 4) {
              role = "Student"
            }
            const userObject = {
              name: `${data?.first_name} ${data?.last_name}`,
              id: data?.id,
              organization_name:data?.organization_name,
              role_id: role,
              activityData:findData?.data
            }
            findUserActivityData.push(userObject)
          } 
          else {
            if(dateString == 'month'){
              const todayDate = moment().format('YYYY-MM-DD')
              const weekDate = moment().startOf('month').format('YYYY-MM-DD');
              findData = await commonQuery(activityModel,find, {userId:data?.id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
              
              let role;
              if (data?.role_id === 2) {
                role = "Organization"
              }
              if (data?.role_id === 3) {
                role = "Instructor"
              }
              if (data?.role_id === 4) {
                role = "Student"
              }
              const userObject = {
                name: `${data?.first_name} ${data?.last_name}`,
                id: data?.id,
                organization_name:data?.organization_name,
                role_id: role,
                activityData:findData?.data
              }
              findUserActivityData.push(userObject)
            }
            else if(dateString == 'week'){
              const todayDate = moment().format('YYYY-MM-DD')
              const weekDate = moment().startOf('week').format('YYYY-MM-DD')
              findData = await commonQuery(activityModel,find, {userId:data?.id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
            
              let role
              if (data?.role_id === 2) {
                role = "Organization"
              }
              if (data?.role_id === 3) {
                role = "Instructor"
              }
              if (data?.role_id === 4) {
                role = "Student"
              }
              
              const userObject = {
                name: `${data?.first_name} ${data?.last_name}`,
                id: data?.id,
                organization_name:data?.organization_name,
                role_id: role,
                activityData:findData?.data
              }
              findUserActivityData.push(userObject)
            
            }
            else if(dateString == 'today'){
              const date = moment().format('YYYY-MM-DD')
              findData = await commonQuery(activityModel,find, {userId:data?.id,date:date},{},['userId','ASC']);
            
              let role
              if (data?.role_id === 2) {
                role = "Organization"
              }
              if (data?.role_id === 3) {
                role = "Instructor"
              }
              if (data?.role_id === 4) {
                role = "Student"
              } 
              const userObject = {
                name: `${data?.first_name} ${data?.last_name}`,
                id: data?.id,
                organization_name:data?.organization_name,
                role_id: role,
                activityData:findData?.data
              }
              findUserActivityData.push(userObject)
            } else {
              const date = moment().format('YYYY-MM-DD')
              findData = await commonQuery(activityModel,find, {userId:data?.id},{},['userId','ASC']);
            
              let role
              if (data?.role_id === 2) {
                role = "Organization"
              }
              if (data?.role_id === 3) {
                role = "Instructor"
              }
              if (data?.role_id === 4) {
                role = "Student"
              } 
              const userObject = {
                name: `${data?.first_name} ${data?.last_name}`,
                id: data?.id,
                organization_name:data?.organization_name,
                role_id: role,
                activityData:findData?.data
              }
              findUserActivityData.push(userObject)
            }
          }
        }))


      const newData =  findUserActivityData?.map((data => {
        return {
          name: data?.name,
          role_id:data?.role_id,
          organization_name:data?.organization_name,
          id: data?.id,
          user_activity : data?.activityData?.map((d) => {
            let newActivity = JSON.parse(d?.activity)
            let forced_time = 0
            let total_time = 0
            newActivity?.map((e) => {
              total_time = total_time + e?.total
              if (e?.title?.includes("Notepad") || e?.title?.includes("Notepad++")) {
                forced_time = forced_time + e?.total
              }
            })
            return {
              activity:{
                login:d?.login,
                logout:d?.logout,
                time_count:{forced_time,total_time}
              }
            }
          })
        }
        }))

        let newUserActivity =  newData?.map((data) => {
          let new_forced_time = 0
          let new_total_time = 0
          data?.user_activity?.map((d) => {
            new_forced_time = new_forced_time + d?.activity?.time_count?.forced_time
            new_total_time = new_total_time + d?.activity?.time_count?.total_time
          })

          return {
            name:data?.name,
            role:data?.role_id,
            organization_name:data?.organization_name,
            id: data?.id,
            total_time:format(Number(new_total_time.toFixed(2))),
            salesforce_time:format(Number(new_forced_time.toFixed(2))),
            last_logout: data?.user_activity[0]?.activity?.logout ? data?.user_activity[0]?.activity?.logout : null,
            last_logout: data?.user_activity[0]?.activity?.logout ? moment(data?.user_activity[0]?.activity?.logout).format('YYYY-MM-DD HH:mm') : null,
          }
        })

        const newActivityUser = newUserActivity?.sort((a,b) => a.id > b.id ? 1 : -1)
        res.status(200).json({ status: 1, message: "User's activity detail" , data:newActivityUser});
    } 
    catch (error) {
      console.log('error', error);
      res.status(400).json({ status: 0, message: COMMON_ERROR });
    }
  },

  // get list of all user from particular organization 
  getOrganizationUsers:async(req,res)=>{
    try {
      const {dateString}=req.body;
      const id = req.body.id
      const to = req?.body?.to
      const from = req?.body?.from
      
      const populateData = [User, Organization]
      const populateDataStudent = [User, Organization, Instructors]
      
      const organizationId = await commonQuery(organizationModel, findOne,{user_id:id})
      const instructorUserData = await commonQuery(instructorModel,find, {organization_id:organizationId?.data?.id},{},"",populateData);
      const studentUserData = await commonQuery(studentModel,find, {organization_id:organizationId?.data?.id},{},"",populateDataStudent);

      const findTrackerName = await commonQuery(TrackerModel, find, {});
      const trackerData = JSON.parse(findTrackerName?.data[0]?.name);

      var findActivityData = []
      await Promise.all(instructorUserData?.data?.map( async(data) => {

        if (to && from) {
          const todayDate = moment(to).format('YYYY-MM-DD')
          const weekDate = moment(from).format('YYYY-MM-DD')
          findData = await commonQuery(activityModel,find, {userId:data?.user_id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
          const userObject = {
            name: `${data?.user?.first_name} ${data?.user?.last_name}`,
            organization_name:data?.organization?.name,
            id: data?.user?.id,
            role_id: "Instructor",
            activityData:findData?.data
          }
          
          findActivityData.push(userObject)
        } else {

        if(dateString == 'month'){
          const todayDate = moment().format('YYYY-MM-DD')
          const weekDate = moment().startOf('month').format('YYYY-MM-DD');
          findData = await commonQuery(activityModel,find, {userId:data?.user_id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
          const userObject = {
            name: `${data?.user?.first_name} ${data?.user?.last_name}`,
            id: data?.user?.id,
            role_id: "Instructor",
            organization_name:data?.organization?.name,
            activityData:findData?.data
          }
          
          findActivityData.push(userObject)
        }
        else if(dateString == 'week'){
          const todayDate = moment().format('YYYY-MM-DD')
          const weekDate = moment().startOf('week').format('YYYY-MM-DD')
          findData = await commonQuery(activityModel,find, {userId:data?.user_id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
          const userObject = {
            name: `${data?.user?.first_name} ${data?.user?.last_name}`,
            id: data?.user?.id,
            organization_name:data?.organization?.name,
            role_id: "Instructor",
            activityData:findData?.data
          }
          
          findActivityData.push(userObject)
        }
        else if(dateString == 'today'){
          const date = moment().format('YYYY-MM-DD')
          findData = await commonQuery(activityModel,find, {userId:data?.user_id,date:date},{},['userId','ASC']);
          const userObject = {
            name: `${data?.user?.first_name} ${data?.user?.last_name}`,
            id: data?.user?.id,
            organization_name:data?.organization?.name,
            role_id: "Instructor",
            activityData:findData?.data
          }
          
          findActivityData.push(userObject)
        } else {
          findData = await commonQuery(activityModel,find, {userId:data?.user_id},{},['userId','ASC']);
          const userObject = {
            name: `${data?.user?.first_name} ${data?.user?.last_name}`,
            id: data?.user?.id,
            organization_name:data?.organization?.name,
            role_id: "Instructor",
            activityData:findData?.data
          }
          
          findActivityData.push(userObject)
        }
      }
      }))

      await Promise.all(studentUserData?.data?.map( async(data) => {

        if (to && from) {
          const todayDate = moment(to).format('YYYY-MM-DD')
          const weekDate = moment(from).format('YYYY-MM-DD')

          studentData = await commonQuery(activityModel,find, {userId:data?.user_id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
         
          const userObject = {
            name: `${data?.user?.first_name} ${data?.user?.last_name}`,
            id: data?.user?.id,
            role_id: "Student",
            organization_name:data?.organization?.name,
            activityData:studentData?.data
          }
          findActivityData.push(userObject)
        } 
        else 
        {
          if(dateString == 'month'){
            const todayDate = moment().format('YYYY-MM-DD')
            const weekDate = moment().startOf('month').format('YYYY-MM-DD');
            studentData = await commonQuery(activityModel,find, {userId:data?.user_id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
            const userObject = {
              name: `${data?.user?.first_name} ${data?.user?.last_name}`,
              id: data?.user?.id,
              role_id: "Student",
              organization_name:data?.organization?.name,
              activityData:studentData?.data
            }
            
            findActivityData.push(userObject)
          }
          else if(dateString == 'week'){
            const todayDate = moment().format('YYYY-MM-DD')
            const weekDate = moment().startOf('week').format('YYYY-MM-DD')
            studentData = await commonQuery(activityModel,find, {userId:data?.user_id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
          
            const userObject = {
              name: `${data?.user?.first_name} ${data?.user?.last_name}`,
              id: data?.user?.id,
              role_id: "Student",
              organization_name:data?.organization?.name,
              activityData:studentData?.data
            }
            
            findActivityData.push(userObject)
          }
          else if(dateString == 'today'){
            const date = moment().format('YYYY-MM-DD')
            studentData = await commonQuery(activityModel,find, {userId:data?.user_id,date:date},{},['userId','ASC']);
            
            const userObject = {
              name: `${data?.user?.first_name} ${data?.user?.last_name}`,
              id: data?.user?.id,
              organization_name:data?.organization?.name,
              role_id: "Student",
              activityData:studentData?.data
            }
            
            findActivityData.push(userObject)
          } 
          else {
            studentData = await commonQuery(activityModel,find, {userId:data?.user_id},{},['userId','ASC']);
            
            const userObject = {
              name: `${data?.user?.first_name} ${data?.user?.last_name}`,
              id: data?.user?.id,
              organization_name:data?.organization?.name,
              role_id: "Student",
              activityData:studentData?.data
            }
            
            findActivityData.push(userObject)
          }
        }    
      }))

      const newData =  findActivityData?.map((data => {
        return {
          name: data?.name,
          role_id:data?.role_id,
          organization_name:data?.organization_name,
          id: data?.id,
          user_activity : data?.activityData?.map((d) => {
            let newActivity = JSON.parse(d?.activity?.toLowerCase())
            let forced_time = 0
            let total_time = 0
            total_time = (newActivity)?.reduce((previousValue, currentValue) => previousValue + currentValue.total,0,);
            d.item = [];
            newActivity?.filter((act) => {
              trackerData.map((a)=>{
                if((act.title).match(a.toLowerCase())){
                  (d.item).push(act)
                }
              })
            })
            forced_time = (d.item).reduce((previousValue, currentValue) => previousValue + currentValue.total,0,);
            
            return {
              activity:{
                login:d?.login,
                logout:d?.logout,
                time_count:{forced_time,total_time},
              }
            }
          })
        }
        }))
     
        let newUserActivity =  newData?.map((data) => {
          let new_forced_time = 0
          let new_total_time = 0
          data?.user_activity?.map((d) => {
            new_forced_time = new_forced_time + d?.activity?.time_count?.forced_time
            new_total_time = new_total_time + d?.activity?.time_count?.total_time
          })

          return {
            name:data?.name,
            role:data?.role_id,
            organization_name:data?.organization_name,
            id: data?.id,
            total_time:format(Number(new_total_time)),
            salesforce_time:format(Number(new_forced_time?.toFixed(2))),
            last_logout: data?.user_activity[0]?.activity?.logout ? data?.user_activity[0]?.activity?.logout : null,
            last_login: data?.user_activity[0]?.activity?.login ? data?.user_activity[0]?.activity?.login : null,
          }
        })
        const newActivityUser = newUserActivity?.sort((a,b) => a.id > b.id ? 1 : -1)
        res.status(200).json({ status: 1, message: "Organization's activity detail" , data:newActivityUser});

    } catch (error) {
      console.log('error', error);
      res.status(400).json({ status: 0, message: COMMON_ERROR });
    }
  },

  // get list of all user from particular Instructor 
  getInstructorUsers:async(req,res)=>{
    try {
      const {dateString}=req.body;
      const id = req.body.id

      const to = req?.body?.to
      const from = req?.body?.from

      const populateDataStudent = [User, Organization, Instructors]
      const instructorUserData = await commonQuery(instructorModel,findOne, {user_id:id});
      const studentInstructorUserData = await commonQuery(studentModel,find, {organization_id:instructorUserData?.data?.organization_id},{},"",populateDataStudent);

      const studentUserData = await commonQuery(studentModel,find, {organization_id:id});
      const findTrackerName = await commonQuery(TrackerModel, find, {});
      const trackerData = JSON.parse(findTrackerName?.data[0]?.name)
      var findActivityData = [];
      
      await Promise.all(studentInstructorUserData?.data?.map( async(data) => {
        if (to && from) {
          const todayDate = moment(to).format('YYYY-MM-DD')
          const weekDate = moment(from).format('YYYY-MM-DD')
          studentData = await commonQuery(activityModel,find, {userId:data?.user_id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
          const userObject = {
            name: `${data?.user?.first_name} ${data?.user?.last_name}`,
            organization_name: data?.organization?.name,
            id:data?.user?.id,
            role_id: "Student",
            activityData:studentData?.data
          }
          
          findActivityData.push(userObject)
        } else {
  
          if(dateString == 'month'){
            const todayDate = moment().format('YYYY-MM-DD')
            const weekDate = moment().startOf('month').format('YYYY-MM-DD');
            studentData = await commonQuery(activityModel,find, {userId:data?.user_id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
            const userObject = {
              name: `${data?.user?.first_name} ${data?.user?.last_name}`,
              id:data?.user?.id,
              organization_name: data?.organization?.name,
              role_id: "Student",
              activityData:studentData?.data
            }
            
            findActivityData.push(userObject)
          }
          else if(dateString == 'week'){
            const todayDate = moment().format('YYYY-MM-DD')
            const weekDate = moment().startOf('week').format('YYYY-MM-DD')
            studentData = await commonQuery(activityModel,find, {userId:data?.user_id,date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
            const userObject = {
              name: `${data?.user?.first_name} ${data?.user?.last_name}`,
              id:data?.user?.id,
              organization_name: data?.organization?.name,
              role_id: "Student",
              activityData:studentData?.data
            }
            
            findActivityData.push(userObject)
          }
          else if(dateString == 'today'){
            const date = moment().format('YYYY-MM-DD')
            studentData = await commonQuery(activityModel,find, {userId:data?.user_id,date:date},{},['userId','ASC']);
            const userObject = {
              name: `${data?.user?.first_name} ${data?.user?.last_name}`,
              id:data?.user?.id,
              organization_name: data?.organization?.name,
              role_id: "Student",
              activityData:studentData?.data
            }
            
            findActivityData.push(userObject)
          } else {
            studentData = await commonQuery(activityModel,find, {userId:data?.user_id},{},['userId','ASC']);
            const userObject = {
              name: `${data?.user?.first_name} ${data?.user?.last_name}`,
              id:data?.user?.id,
              organization_name: data?.organization?.name,
              role_id: "Student",
              activityData:studentData?.data
            }
            
            findActivityData.push(userObject)
          }
        }
      }))

      const newData =  findActivityData?.map((data => {
        return {
          name: data?.name,
          role_id:data?.role_id,
          organization_name: data?.organization_name,
          id:data?.id,
          user_activity : data?.activityData?.map((d) => {
            let newActivity = JSON.parse(d?.activity.toLowerCase())
            let forced_time = 0
            let total_time = 0
            total_time = (newActivity)?.reduce((previousValue, currentValue) => previousValue + currentValue.total,0,);
            d.item = [];
            newActivity?.filter((act) => {
              trackerData.map((a)=>{
                if((act.title).match(a.toLowerCase())){
                  (d.item).push(act)
                }
              })
            })
            forced_time = (d.item).reduce((previousValue, currentValue) => previousValue + currentValue.total,0,);
            
            return {
              activity:{
                login:d?.login,
                logout:d?.logout,
                time_count:{forced_time,total_time}
              }
            }
          })
        }
        }))

        let newUserActivity =  newData?.map((data) => {
          let new_forced_time = 0
          let new_total_time = 0
          data?.user_activity?.map((d) => {
            new_forced_time = new_forced_time + d?.activity?.time_count?.forced_time
            new_total_time = new_total_time + d?.activity?.time_count?.total_time
          })
  
          return {
            name:data?.name,
            role:data?.role_id,
            organization_name: data?.organization_name,
            id:data?.id,
            total_time:format(Number(new_total_time.toFixed(2))),
            salesforce_time:format(Number(new_forced_time.toFixed(2))),
            last_logout: data?.user_activity[0]?.activity?.logout ? data?.user_activity[0]?.activity?.logout : null,
            last_login: data?.user_activity[0]?.activity?.login ? data?.user_activity[0]?.activity?.login : null,
          }
        })
        const newActivityUser = newUserActivity?.sort((a,b) => a.id > b.id ? 1 : -1)

      res.status(200).json({ status: 1, message: "Instructor's activity detail" ,  data:newActivityUser});
      
    } catch (error) {
      console.log('error', error);
      res.status(400).json({ status: 0, message: COMMON_ERROR });
    }
  },

  // admin
  // activity detail of particular user
  particularUser : async(req,res)=>{
    try {
      const {dateString}=req.body;
      const studentData = await commonQuery(studentModel,findOne,{user_id:req?.body?.id});
      const organization = await commonQuery(organizationModel,findOne,{id:studentData?.data?.organization_id});
      const findTrackerName = await commonQuery(TrackerModel, find, {});
      const trackerData = JSON.parse(findTrackerName?.data[0]?.name);
      
      var findData;
      const to = req?.body?.to
      const from = req?.body?.from

      if (to && from) { 
        const todayDate = moment().format('YYYY-MM-DD')
        const weekDate = moment().add(-7, 'day').format('YYYY-MM-DD')
        findData = await commonQuery(activityModel,find, {userId:req?.body?.id , date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
      } 
      else {
        if(dateString == 'month'){
          const todayDate = moment().format('YYYY-MM-DD')
          const weekDate = moment().startOf('month').format('YYYY-MM-DD');
          
          findData = await commonQuery(activityModel,find, {userId:req?.body?.id , date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
        }
        else if(dateString == 'week'){
          const todayDate = moment().format('YYYY-MM-DD')
          const weekDate = moment().startOf('week').format('YYYY-MM-DD')
          findData = await commonQuery(activityModel,find, {userId:req?.body?.id , date:{[Op.gte]:weekDate, [Op.lte]:todayDate}},{},['userId','ASC']);
        }
        else if(dateString == 'today'){
          const date = moment().format('YYYY-MM-DD')
          findData = await commonQuery(activityModel,find, {userId:req?.body?.id , date:date},{},['userId','ASC']);
        } else {
          findData = await commonQuery(activityModel,find, {userId:req?.body?.id},{},['userId','ASC']);
        }
      }

      await Promise.all(findData?.data.map((e)=>{
        e.activity =JSON.parse(e?.activity?.toLowerCase());
        e.item = []
        e.activity = e?.activity.filter((act)=>{
          trackerData.map((a)=>{
            if((act.title).match(a.toLowerCase())){
              (e.item).push(act);
              return e
            }
          })
        })
        e.totalTime = format((e.item).reduce((previousValue, currentValue) => previousValue + currentValue.total,0,));
        return e;
      }))

      if (findData.status == 1) {

        const newFindData = findData?.data?.map((data) => {
          return {
            id:data?.id,
            userId:data?.userId,
            organization_name: organization?.data?.name,
            date: data?.date,
            login: moment(data?.login).format('YYYY-MM-DD HH:mm'),
            logout: moment(data?.logout).format('YYYY-MM-DD HH:mm'),
            created_at: data?.created_at,
            updated_at: data?.updated_at,
            item: data?.item?.map((d) => {
              return {
                title: d?.title,
                total: format(d?.total)
              }
            }),   
            totalTime:data?.totalTime
          }
        })

        res.status(200).json({ status: 1, message: "User's activity detail" , data:newFindData});
      } else {
        res.status(400).json({ status: 0, message: COMMON_ERROR });
      }
    } catch (error) {
      console.log('error', error);
      res.status(400).json({ status: 0, message: COMMON_ERROR });
    }
  },
  
};
