const Amadeus = require('amadeus');
const fs = require('fs');

const amadeus = new Amadeus({
  clientId: '4YW2bmhwYKjothBbv8eU7tsUR5XytpXj',
  clientSecret: 'lyLGo5Qt0LGkGo6Y'
});

amadeus.shopping.flightOffersSearch.get({
    originLocationCode: 'SYD',
    destinationLocationCode: 'BKK',
    departureDate: '2025-06-01',
    adults: '2'
}).then(function(response){
  fs.writeFile('responseData.json', JSON.stringify(response.data, null, 2), (err) => {
    if (err) throw err;
    console.log('Response data saved to responseData.json');
  });
}).catch(function(responseError){
  console.log(responseError.code);
});