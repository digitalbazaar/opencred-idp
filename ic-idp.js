var async = require('async');
var forge = require('node-forge');
var path = require('path');
var th = require('telehash');

// open credential query channel
var ocQueryChannel = 'ocQuery';

// the mapping database
var mappingDb = {};

/******************** Packet Handlers *********************/

function idpPacketHandler(err, packet, chan, callback) {
  // check for error
  if(err) {
    return console.log('idp: packet error', err);
  }
  var message = packet.js;

  // received packet
  console.log('idp received:', message);

  if(message.type === 'Query' && 'query' in message) {
    if(message.query in mappingDb) {
      chan.send({js: mappingDb[message.query]});
    }
  }

  // send an ack and recieve subsequent packets
  callback(true);
}

/***************** Identity Provider Init ******************/
var hashnameFile = path.join(process.cwd(), 'hashname-ic-idp.json');
th.init({id: hashnameFile}, function(err, hashname) {
  if(err) {
    return console.log("IdP startup failed", err);
  }

  async.auto({
    joinNetwork: function(callback) {
      // join the query channel
      hashname.listen(ocQueryChannel, idpPacketHandler);
      console.log('idp debug: listening on '+ ocQueryChannel);
      callback();
    }
  }, function(err, results) {
    if(err) {
      return console.log('idp error:', err);
    }
    console.log('idp debug: IdP is online');
  });

});

/******************** Express ******************************/
var bodyParser = require('body-parser');
var express = require('express');
var app = express();

// parse application/json and application/x-www-form-urlencoded
app.use(bodyParser())

// parse application/ld+json as json
app.use(bodyParser.json({type: 'application/ld+json'}));

app.post('/register', function(req, res) {
  // FIXME: protect against attacks from localhost
  if('type' in req.body && req.body.type === 'IdentityProviderMapping' &&
    'query' in req.body && 'queryResponse' in req.body) {
    mappingDb[req.body['query']] = req.body['queryResponse'];
    console.log('idp debug: mapping database updated', mappingDb);
    res.status(200);
    res.send('Mapping added to database.');
  } else {
    res.status(400);
    res.send('IdentityProviderMapping was invalid.');
  }

});

app.listen(42425, 'localhost');