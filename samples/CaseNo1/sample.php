<?php

//sample normal loop
$customers = Customers::find(["name LIKE '%test%'"]); // Error: name didn't exists in customers table
$customers = Customers::find(['points > 10']); // it is OK
foreach ($customers as $customer) {
    $customer->name = ($customer->gendre == 'Man' ? 'Mr.' : 'Mrs.') . $customer->name;
    $customer->save();
}

// sample full loop
$customers = Customers::fullFind(["name LIKE '%test%' AND points > 10"]); // it is OK
foreach ($customers as $customer) {
    $customer->name = ($customer->gendre == 'Man' ? 'Mr.' : 'Mrs.') . $customer->name;
    Customers::toObject($customer)->save();
}

//sample normal find first
$customer = Customers::findFirst(["name LIKE '%test%'"]); // Error: name didn't exists in customers table
$customer = Customers::findFirst(['points > 10']); // it is OK

//sample full find first
$customer = Customers::findFirst(["name LIKE '%test%' AND points > 10"]); // it is OK


$customer = Customers::findFirstByName('test'); // Not permitted
$customer = Customers::fullFindFirstByName('test'); // it is ok

//other samples
$result = Customers::fullCount(["name LIKE '%test%' AND points > 10"]);
$result = Customers::fullSum(["name LIKE '%test%' AND points > 10", 'column' => 'points']);
$result = Customers::fullAverage(["name LIKE '%test%' AND points > 10", 'column' => 'points']);
$result = Customers::fullMaximum(["name LIKE '%test%' AND points > 10", 'column' => 'points']);
$result = Customers::fullMinimum(["name LIKE '%test%' AND points > 10", 'column' => 'points']);