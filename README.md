# Phalcon-Model-Middleware
Phalcon php framework is a powerfull framework for creating big scale projects that focuses on optimization. but there is no polymorphic relationship between models. I solve this problem by making a trait that can be placed in middleware of two classes o join them as a single model. in this presentation we can have one model that have actually two or more tables in database. using this trait is like playing with knife! you must know that you ar doing.

## Sample Case #1 (Diffrent Types of Users)
### The Problem
Imaging a system that have a parent class named Users. each user can have login to system, so there is a table named users. we have another class named Customers that extends Users, so Customers can login to system and submit order some products. we have another class named Operators that extends Users, so they can login to system but they cann't submit order but they can view admin area. in UML these are 3 simple classes with 2 extends. in database these can have 3 tables with relations. but in phalcon using this 3 tables are confusing. everytime you must join tables to find records that you want. but with this triat all things going right.

### The Solution
you must create 3 tables named users,customers and operators all tables have a primary key named id. but customers and operators id field have a foreign key with users.id field. then create 3 models : Users, Customers, Operators. then create an middle class named UsersMiddleware. Users must extends Phalcon\Mvc\Model. UsersMiddleware is an abstract class and extends Users and use IVIR3zaM/Phalcon-Model-Middleware/ModelsMiddleware as a trait. Customers and Operators extends UsersMiddleware. know you can add a function named getUniqueField() to UsersMiddleware to specify what unique field is the foreign key of all 3 tables. in Users model you must have at least one field that specify the type of User. lets call that type. in our case type can be Customer or Operator. know you must add a function named getCustomFields() in Customers and Operatos that return what columns must use for detecting the type of users and what data they must to have. for Customers in can return ['type' => 'Customer'] and for Operators it can return ['type' => 'Customer']. your done!

### Use-Case
know you can find customers like this: `Customers::find(['id > 10'])` and get customers objects in full filled attributes. but there is a single problem. my trait use afterFetch() hook to fill all attributes of your Customers objects. so when you loop on your Customers for each Customer one SELECT query will made up to fetch Users data of that Customer! so if you wanna loop on too many records don't use this function instead, use fullFind function like this `Customers::fullFind(['id > 10'])`. in this state all data will fetched with a single query by a join on two tables. but in this case fullFind will return an array of Phalcon\Mvc\Model\Row objects! this is for phalcon policy that return Row objects on joined queries. you can use this objects instead of original ones in view, but if you need the actual objects to do some jobs like updating data simply do it like this:

```php
$customers = Customers::fullFind();
foreach($customers as $customer) {
    $customer->name = ($customer->gendre == 'Man'?'Mr.':'Mrs.').$customer->name;
    Customers::toObject($customer)->save();
}
```
