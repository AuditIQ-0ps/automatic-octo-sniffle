//handle setupevents as quickly as possible
const setupEvents = require('../../installers/setupEvents');
if (setupEvents.handleSquirrelEvent()) {
  // squirrel event handled and app will exit in 1000ms, so don't do anything else
  return;
}

import path from 'path';
import {
  app,
  BrowserWindow,
  shell,
  ipcMain,
  ipcRenderer,
  Tray,
  nativeImage,
  Menu,
} from 'electron';
import { autoUpdater } from 'electron-updater';
import log from 'electron-log';
import MenuBuilder from './menu';
import { resolveHtmlPath } from './util';

const fs = require('fs-extra');
const _ = require('lodash');
var spawn = require('child_process').spawn;
var executablePath = app.isPackaged
  ? path.join(process.resourcesPath, 'extraResource/main/main.exe')
  : path.join(__dirname, '../../extraResource/main/main.exe');

class AppUpdater {
  constructor() {
    log.transports.file.level = 'info';
    autoUpdater.logger = log;
    autoUpdater.checkForUpdatesAndNotify();
  }
}

let mainWindow: BrowserWindow | null = null;

ipcMain.on('ipc-example', async (event, arg) => {
  const msgTemplate = (pingPong: string) => `IPC test: ${pingPong}`;
  event.reply('ipc-example', msgTemplate('pong'));
});

var Interval: any;

//track activity class
class ActivityTracker {
  filePath: any;
  interval: any;
  start: any;
  app: any;

  constructor(filePath: any, interval: any) {
    this.filePath = filePath;
    this.interval = interval;
    this.start = null;
    this.app = null;
  }

  track() {
    try {
      Interval = setInterval(async () => {
        var window = { app: '' };

        const ls = spawn(executablePath, []);
        ls.stdout.setEncoding('utf8');

        ls.stdout.on('data', (stdout: any) => {
          var app = stdout.toString();
          app = app.split('App:')[1]?.trim();
          window.app = app;
          if (!this.app) {
            this.start = new Date();
            this.app = window;
          }
          if (window?.app !== this.app?.app) {
            this.storeData();
            this.app = null;
          }
        });

        //Obtain error response from script
        ls.stderr.on('data', function (stderr: any) {
          console.log('error :: :: ::', stderr.toString());
        });

        ls.stdin.end();
      }, this.interval);
    } catch (error) {
      // console.log('error', error);
    }
  }

  async storeData() {
    try {
      let content = fs.readJsonSync(this.filePath);
      const time = {
        start: this.start,
        end: new Date(),
      };
      const time2: any = time.end;

      const { app } = this?.app;
      _.defaultsDeep(content, { [app]: { time: 0 } });
      content[app].time += Math.abs(time.start - time2) / 1000;
      const newContent = JSON.stringify(content);

      fs.writeFileSync(this.filePath, newContent, { spaces: 2 }, (err: any) => {
        console.log(err);
      });
    } catch (error) {
      if (fs.existsSync(path.join(__dirname, this.filePath)) == false) {
        clearInterval(Interval);
      }
    }
  }

  async init() {
    const fileExists = await fs.pathExists(this.filePath);
    if (!fileExists) {
      //If file is not exist write file
      fs.writeJsonSync(this.filePath, {});
    }
    this.track();
  }

  async clearInterval() {
    clearInterval(Interval);
  }

  isEmpty(obj: any) {
    for (var prop in obj) {
      if (Object.prototype.hasOwnProperty.call(obj, prop)) {
        return false;
      }
    }
    return JSON.stringify(obj) === JSON.stringify({});
  }
}

//get activity track data in json format
const readFileJson = (filePath: any) => {
  let data = JSON.parse(fs?.readFileSync(filePath, { encoding: 'utf-8' }));

  const formatedData: any = [];
  Object.entries(data)?.forEach(([key, val]: any) => {
    formatedData.push({
      title: key,
      total: val.time,
    });
  });

  return formatedData;
};

ipcMain.on('active-window', async (event, arg) => {
  const tracker = new ActivityTracker(`${arg}.json`, 2000);
  tracker.init();
});

ipcMain.on('clear-interval', async (event, arg) => {
  const tracker = new ActivityTracker(`${arg}.json`, 2000);
  tracker.clearInterval();
  fs.writeJsonSync(`${arg}.json`, {});
});

ipcMain.on('read-file', async (event, arg) => {
  const a = readFileJson(`${arg}.json`);
  event.reply('read-file', a);
});

if (process.env.NODE_ENV === 'production') {
  const sourceMapSupport = require('source-map-support');
  sourceMapSupport.install();
}

const isDebug =
  process.env.NODE_ENV === 'development' || process.env.DEBUG_PROD === 'true';

if (isDebug) {
  require('electron-debug')();
}

const installExtensions = async () => {
  const installer = require('electron-devtools-installer');
  const forceDownload = !!process.env.UPGRADE_EXTENSIONS;
  const extensions = ['REACT_DEVELOPER_TOOLS'];

  return installer
    .default(
      extensions.map((name) => installer[name]),
      forceDownload
    )
    .catch(console.log);
};

let tray: any = null,
  isQuiting: any;

app.on('before-quit', function (event) {
  isQuiting = true;
});

//keep app running in background
function createTray() {
  const RESOURCES_PATH = app.isPackaged
    ? path.join(process.resourcesPath, 'assets')
    : path.join(__dirname, '../../assets');

  const getAssetPath = (...paths: string[]): string => {
    return path.join(RESOURCES_PATH, ...paths);
  };
  const icon = getAssetPath('marro-logo.png'); // required.
  const trayicon = nativeImage.createFromPath(icon);
  tray = new Tray(trayicon.resize({ width: 16 }));
  const contextMenu = Menu.buildFromTemplate([
    {
      label: 'Show',
      click: () => {
        mainWindow?.show();
      },
    },
    {
      label: 'Quit',
      click: function () {
        isQuiting = true;
        mainWindow && mainWindow?.webContents.send('log-out-on-quit', "log-out");
        setTimeout(()=>{
          app.quit();
        },2000)
        mainWindow = null;
        tray = null;
      },
    },
  ]);

  tray.setContextMenu(contextMenu);
}

const createWindow = async () => {
  if (isDebug) {
    await installExtensions();
  }

  const RESOURCES_PATH = app.isPackaged
    ? path.join(process.resourcesPath, 'assets')
    : path.join(__dirname, '../../assets');

  const getAssetPath = (...paths: string[]): string => {
    return path.join(RESOURCES_PATH, ...paths);
  };

  const iconPath =
    process.platform !== 'darwin'
      ? getAssetPath('marro-logo.png')
      : getAssetPath('marro-logo.icns');

  mainWindow = new BrowserWindow({
    show: false,
    minWidth: 1024,
    minHeight: 728,
    width: 1024,
    height: 728,
    icon: iconPath,
    webPreferences: {
      sandbox: false,
      preload: app.isPackaged
        ? path.join(__dirname, 'preload.js')
        : path.join(__dirname, '../../.erb/dll/preload.js'),
    },
    titleBarStyle: 'hidden',
  });

  mainWindow.loadURL(resolveHtmlPath('index.html'));

  mainWindow.on('ready-to-show', () => {
    if (process.env.START_MINIMIZED) {
      mainWindow?.minimize();
    } else {
      mainWindow?.show();
    }
  });

  const menuBuilder = new MenuBuilder(mainWindow);
  menuBuilder.buildMenu();

  // Open urls in the user's browser
  mainWindow.webContents.setWindowOpenHandler((edata) => {
    shell.openExternal(edata.url);
    return { action: 'deny' };
  });

  //close app
  ipcMain.on('close-btn', async (event) => {
    if (!isQuiting) {
      event.preventDefault();
      mainWindow?.hide();
      event.returnValue = false;
    }
  });

  //prevent multiple instance
  const gotTheLock = app.requestSingleInstanceLock();
  if (!gotTheLock) {
    app.quit();
  } else {
    app.on('second-instance', (event, commandLine, workingDirectory) => {
      if (mainWindow) {
        if (mainWindow.isMinimized()) {
          mainWindow.restore();
        } else {
          if (!mainWindow.isVisible()) {
            mainWindow.show();
          }
        }
        mainWindow.focus();
      }
    });
  }
  mainWindow.once('focus', () => mainWindow?.flashFrame(false));
  
  if (!tray) {
    // if tray hasn't been created already.
    createTray();
  }

  //minimize app
  ipcMain.on('minimize-btn', async () => {
    mainWindow?.minimize();
  });

  //maximize app
  ipcMain.on('maximize-btn', async () => {
    if (mainWindow?.isMaximized()) {
      mainWindow.restore();
    } else {
      mainWindow?.maximize();
    }
  });

  // Remove this if your app does not use auto updates
  // eslint-disable-next-line
  new AppUpdater();
};

/**
 * Add event listeners...
 */

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
    mainWindow = null;
    tray = null;
  }
});

app
  .whenReady()
  .then(() => {
    createWindow();
    // mainWindow?.webContents.openDevTools();
    app.on('activate', () => {
      if (mainWindow === null) createWindow();
    });
  })
  .catch(console.log);
