import { MemoryRouter as Router, Routes, Route } from 'react-router-dom';
import Login from './Components/Login';
import Home from './Components/Home';
import Titlebar from './Components/Titlebar';
import 'bootstrap/dist/css/bootstrap.min.css';
import './App.css';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

export default function App() {
  const existUser = localStorage.getItem('email');
  return (
    <>
      <div className="main-page">
      <Titlebar />
        <Router>
          <ToastContainer style={{ textTransform: 'capitalize' , zIndex:10000, marginTop:'25px'}} />
          <Routes>
            <Route path="/" element={!existUser ? <Login /> : <Home />} />
            <Route path="/login" element={<Login />} />
            <Route path="/main-page" element={<Home />} />
          </Routes>
        </Router>
      </div>
    </>
  );
}
