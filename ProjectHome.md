SDML allows for rapid prototyping of MySQL databases. It's original intend was to mark up databases in a format that could be parsed during setup processes of web applications.

SDML is best explained by looking at an example markup.
```
database library
  user librarian secretpassword
  table book
    uint64      !+id
    string8     title
    uint32      -author
    string24    ?description
    timestamp   releaseDate
    ctimestamp  dateAdded
    mtimestamp  dateModified

  table author
    uint32      !+id
    string8     name

  table authorBooks
    uint32      -authorId
    uint64      -bookId

  constraint authorBooks.authorId author.id
  constraint authorBooks.bookId   book.id
```

Passing this markup to the SDML parser would result in this output:
```
DROP DATABASE IF EXISTS `library`;
CREATE DATABASE `library` CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE TABLE `library`.`book` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT, 
PRIMARY KEY (`id`), 
`title` tinytext NOT NULL, 
`author` int unsigned NOT NULL, 
KEY `author_Index` (`author`), 
`description` mediumtext , 
`releaseDate` timestamp NOT NULL, 
`dateAdded` timestamp NOT NULL, 
`dateModified` timestamp NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TRIGGER `library`.`onbookCreated` BEFORE INSERT ON `library`.`book` FOR EACH ROW BEGIN SET NEW.dateAdded = NOW();SET NEW.dateModified = NOW(); END;
CREATE TRIGGER `library`.`onbookUpdated` BEFORE UPDATE ON `library`.`book` FOR EACH ROW BEGIN SET NEW.dateModified = NOW(); END;
CREATE TABLE `library`.`author` (`id` int unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`), `name` tinytext NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `library`.`authorBooks` (`authorId` int unsigned NOT NULL, KEY `authorId_Index` (`authorId`), `bookId` bigint unsigned NOT NULL, KEY `bookId_Index` (`bookId`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `library`.`authorBooks` ADD CONSTRAINT FK_authorBooks_authorId FOREIGN KEY (`authorId`) REFERENCES `library`.`author` (`id`);
ALTER TABLE `library`.`authorBooks` ADD CONSTRAINT FK_authorBooks_bookId FOREIGN KEY (`bookId`) REFERENCES `library`.`book` (`id`);
GRANT USAGE ON *.* TO 'librarian'@'localhost';
DROP USER 'librarian'@'localhost';
CREATE USER 'librarian'@'localhost' IDENTIFIED BY 'secretpassword';
GRANT ALL PRIVILEGES ON library.* TO 'librarian'@'localhost';
```
_additional line breaks were added here to improve display_

In SDML, the indentation of a line defines in what scope the line will be evaluated. For example, the first line defines a `database`. A database is always at the top level. All other statements should result in creating object within that database.

```
database library
```
So, the first line creates a database named "library". It will be created with all default parameters. That is `utf8` character set and `utf8_general_ci` collate. These parameters could also be defined after the database name.

```
  user librarian secretpassword
```
Now we create a new user, named "librarian" with the password "secretpassword". The users defined in SDML are usually the users used by applications to connect to the database. The user will have full privileges on the database he was defined in.

```
  table book
    uint64      !+id
    string8     title
    uint32      -author
    string24    ?description
    timestamp   releaseDate
    ctimestamp  dateAdded
    mtimestamp  dateModified
```
Now it's time to define the first table inside the database. This is done by using the `table` keyword. Tables are by default created with the InnoDB engine and with `utf8` charset. Other engines and charsets can be supplied after the name of the table (but engines other than InnoDB are not tested).

Inside the table, we can define the columns of the table. We do that by defining the type of the column first. The `int` and `uint` types map to the `INT` types of MySQL. The `string` types map to the MySQL `TEXT` types. They should be rather self-explanatory. If not, right now you could check `translateTypes` in `ColumnToken.php`.

However, there are a few special types. Like `ctimestamp` and `mtimestamp`. When these are used, a trigger will be defined that updates the fields when a new row is created or updated respectively. This was created with fields like `dateCreated` or `dateModified` in mind.

Every column name can contain certain prefixes:
  * ! Primary key
  * - Create an index for this column
  * ? This column can be NULL
  * + Auto-increment
  * 1 Unique key

Everything after the column name will be used as the default value of the column.

```
  constraint authorBooks.authorId author.id
  constraint authorBooks.bookId   book.id
```
Constraints is the last thing we'll look at. All it currently does is creating foreign key constraints between columns. The syntax should be obvious.

Not shown in the example is `insert`. It allows you to insert data into a table. Thus it should be placed inside the scope of a table. It expects the first parameter to be the delimiter for the remaining parameters. And then an amount of parameters equal to the amount of columns previously defined in the table. For example:
```
  insert | 12|UNHEX("F08B41BD2F0EFE42A787C5AA4631F128")|16|"comments, more comments"|1|"muh"|"2010-07-26 15:05:59"|"2010-07-26 15:05:59"|0
```

Especially the `insert` keyword can also be used with the `sequence` keyword.
```
  sequence 1 110 {i} insert , {i},1
```
This would be equivalent to having 110 `insert` lines with `{i}` being replaced by the numbers from 1 to 110.

You can compile SDML files by using the `parseSdml.php` CLI application. It can also connect to a database and run the result.

There are other keywords (like "key") and (side)effects which still have to be documented. But the feature set and language details still have to solidify more until thorough documentation becomes reasonable.