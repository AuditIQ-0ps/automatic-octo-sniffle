import React, { useState, useEffect } from 'react';
import { Form, Button } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import { toastStyle } from './ToastStyle';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {  faEye, faEyeSlash } from '@fortawesome/free-solid-svg-icons';
// import { signIn } from '../../main/API/apiConfig';
const { ipcRenderer, nodeApi } = window?.electron;
const { signIn } = nodeApi;

const Login = () => {
  const [password, setPassword] = useState('');
  const [email, setEmail] = useState('');
  const [passwordType, setPasswordType]=useState('password');
  const [eye,setEye]=useState(true);
  const navigate = useNavigate();

  useEffect(() => {
    if (localStorage.getItem('logout-msg') == 'true') {
      toast.success('Logged out successfully', toastStyle);
      localStorage.setItem('logout-msg', 'false');
    }
  }, []);

  const toggleShowHide = () => {
    if (passwordType === "password") {
      setPasswordType("text");
      setEye(false);
    } else {
      setPasswordType("password");
      setEye(true);
    }
  };

  const submitHandler = async (e: any) => {
    e.preventDefault();
    const data = { email, password };
    if (!email && !password) {
      return toast.error('Please enter details...!', toastStyle);
    }
    if (!email) {
      return toast.error('Please enter Email...!', toastStyle);
    }
    if (!password) {
      return toast.error('Please enter Password...!', toastStyle);
    }
    
    const res = await signIn(data);
    if (res.status == 1) {
      //start tracking user's activity 
      ipcRenderer.sendMessage('active-window', email);
      localStorage.setItem('email', email);
      localStorage.setItem('token', res?.token);
      toast.success('Logged in successfully', toastStyle);
      navigate('/main-page');
    } else {
      toast.error(res.response.data.message, toastStyle);
    }
  };

  return (
    <div className="main-div">
      <Form onSubmit={submitHandler} className=" from">
        <h1 className="text-center">Log In</h1>
        <Form.Group className="mb-3 mt-3" controlId="formBasicEmail">
          <Form.Label>Email address</Form.Label>
          <Form.Control
            type="text"
            placeholder="Enter email"
            value={email}
            onChange={(e) => setEmail((e.target as HTMLInputElement).value)}
          />
        </Form.Group>

        <Form.Group className="mb-3 password-form" controlId="formBasicPassword">
          <Form.Label>Password</Form.Label>
          <Form.Control
            type={passwordType}
            placeholder="Enter password"
            value={password}
            onChange={(e) => setPassword((e.target as HTMLInputElement).value)}
          />
          <span onClick={()=>toggleShowHide()}>
            { eye ? <FontAwesomeIcon icon={faEye} className="text-dark" /> : <FontAwesomeIcon icon={faEyeSlash} className="text-dark" /> }
          </span>
        </Form.Group>

        <Button variant="primary" type="submit" className="submit-btn">
          Submit
        </Button>
      </Form>
    </div>
  );
};

export default Login;
