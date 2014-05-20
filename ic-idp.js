var async = require('async');
var fs = require('fs');
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
    loadDatabase: function(callback) {
      // save the mapping database to disk
      fs.readFile('ic-idp.db.json', function(err, data) {
        if(err) {
          console.log(
            'idp warning: Failed to load mapping DB from disk -', err);
          callback();
        } else {
          mappingDb = JSON.parse(data);
          console.log(
            'idp debug: Loaded mapping database from ic-idp.db.json.');
          callback();
        }
      });
    },
    joinNetwork: ['loadDatabase', function(callback) {
      // join the query channel
      hashname.listen(ocQueryChannel, idpPacketHandler);
      console.log('idp debug: listening on '+ ocQueryChannel);
      callback();
    }]
  }, function(err) {
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
app.use(bodyParser());

// parse application/ld+json as json
app.use(bodyParser.json({type: 'application/ld+json'}));

app.post('/register', function(req, res) {
  // FIXME: protect against attacks from localhost
  if('type' in req.body && req.body.type === 'IdentityProviderMapping' &&
    'query' in req.body && 'queryResponse' in req.body) {
    // add the query response to the mapping database
    mappingDb[req.body.query] = req.body.queryResponse;
    console.log('idp debug: mapping database updated', mappingDb);
    res.status(201);
    res.send('Mapping added to database.');

    // save the mapping database to disk
    fs.writeFile('ic-idp.db.json', JSON.stringify(mappingDb), function(err) {
      if(err) {
        console.log('idp error: Failed to write mapping DB to disk -', err);
      }
    });
  } else {
    res.status(400);
    res.send('IdentityProviderMapping was invalid.');
  }

});

app.listen(42425, 'localhost');