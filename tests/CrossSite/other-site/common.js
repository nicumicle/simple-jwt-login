// Shared by the pages of the "other site":
//   ?m=<username to create>&target=<WordPress base URL>[&cred=header|cookie]#jwt=<token>
// The JWT travels in the URL fragment, which the browser never sends to a server.
const params = new URLSearchParams(location.search);
const marker = params.get('m') || 'cross_site_' + Date.now();
const target = (params.get('target') || 'http://localhost:88').replace(/\/$/, '');
const newAdmin = {
  username: marker,
  email: marker + '@example.invalid',
  roles: 'administrator',
  password: 'Xk9qz_other_site_pw',
};
