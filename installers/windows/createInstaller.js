const createWindowsInstaller = require('electron-winstaller').createWindowsInstaller
const path = require('path')

getInstallerConfig()
  .then(createWindowsInstaller)
  .catch((error) => {
    console.error(error.message || error)
    process.exit(1)
  })

function getInstallerConfig () {
  console.log('creating windows installer')
  const rootPath = path.join('./')
  const outPath = path.join(rootPath, 'release')

  return Promise.resolve({
    version:'1.1.0',
    appDirectory: path.join(outPath, 'build','win-unpacked'),
    authors: 'Marro App Tracker',
    noMsi: true,
    outputDirectory: path.join(outPath,'build' ,'windows-installer'),
    exe: 'Marro App Tracker.exe',
    setupExe: 'Marro App Tracker.exe',
    setupIcon: path.join(rootPath, 'assets', 'marro-logo.ico')
  })
}