
const Amadeus = require('amadeus');
const fs = require('fs');

const amadeus = new Amadeus({
  clientId: '4YW2bmhwYKjothBbv8eU7tsUR5XytpXj',
  clientSecret: 'lyLGo5Qt0LGkGo6Y'
});

let origin,origin_code,destination,destination_code,departureDate,adults;

document.getElementById('flight_search_button').addEventListener('click', function(){
  origin=document.getElementById('departure_airport').value;
  origin_code=origin.substring(-1,-4);
  destination=document.getElementById('arrival_airport').value;
  destination_code=destination.substring(-1,-4);
  var date = departureDate = document.getElementById('departure_date').value;
  var reversedDate = date.split('-').reverse().join('-');
  adults=document.getElementById('adults').value;
  amadeus.shopping.flightOffersSearch.get({
      originLocationCode: origin_code,
      destinationLocationCode: destination_code,
      departureDate: date,
      adults: adults
  }).then(function(response){
    fs.writeFile('responseData.json', JSON.stringify(response.data, null, 2), (err) => {
      if (err) throw err;
      console.log('Response data saved to responseData.json');
    });
  }).catch(function(responseError){
    console.log(responseError);
  });
});

/*amadeus.shopping.flightOffersSearch.get({
    originLocationCode: 'BOM',
    destinationLocationCode: 'BKK',
    departureDate: '2025-06-01',
    adults: '2'
}).then(function(response){
  fs.writeFile('responseData.json', JSON.stringify(response.data, null, 2), (err) => {
    if (err) throw err;
    console.log('Response data saved to responseData.json');
  });
}).catch(function(responseError){
  console.log(responseError);
});
*/