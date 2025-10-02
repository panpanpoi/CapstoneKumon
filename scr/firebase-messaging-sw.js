importScripts("https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js");
importScripts("https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging.js");

firebase.initializeApp({
  apiKey: "AIzaSyAcnlBtzPF9z6ahVDlPAjXOpgHlr2BIapc",
  authDomain: "kumonnotification-53518.firebaseapp.com",
  projectId: "kumonnotification-53518",
  storageBucket: "kumonnotification-53518.firebasestorage.app",
  messagingSenderId: "346058075092",
  appId: "1:346058075092:web:20e02a2cf742e7d3f84441",
  measurementId: "G-YETFSFV1NL"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  self.registration.showNotification(payload.notification.title, {
    body: payload.notification.body,
    icon: payload.notification.icon
  });
});
