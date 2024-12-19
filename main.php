<?php

require 'example_persons.php'; // подключаем файл с массивом пользователей

// функция принимает строку с ФИО и возвращает ассоциированный массив, в котором содержатся фамилия, имя и отчество по отдельности
function getPartsFromFullname($fullnameAsString)
{
    return array_combine(['surname', 'name', 'patronymic'], explode(' ', $fullnameAsString));
}

// Функция принимает фамилию, имя и отчество в виде строк и возвращает в виде одной строки, с пробелом, в качестве разделителя.
function getFullnameFromParts($surname, $name, $patronymic)
{
    return "$surname $name $patronymic";
}

// функция принимает ФИО в виде строки и возвращает сокращенный вариант в виде имени и первой буквы фамилии (прим.: Иван И.)
function getShortName($fullnameAsString)
{

    // создаем ассоциативный массив на основе строки с ФИО
    $nameAsArray = getPartsFromFullname($fullnameAsString);

    // берем из массива имя целиком и первую букву фамилии и объединяем в строку
    return $nameAsArray['name'] . ' ' . mb_substr($nameAsArray['surname'], 0, 1) . '.';
}

// Функция по определению пола на основе имени. Принимает ФИО в виде строки, возвращает количество баллов. Если баллы меньше 0, пол - женский, больше 0, пол - мужской, равны 0, пол не удалось определить.
function getGenderFromName($fullnameAsString)
{

    // создаем ассоциативный массив на основе строки с ФИО
    $fullnameAsArray = getPartsFromFullname($fullnameAsString);

    // суммарный признак пола, на основе которого будем определять пол человека
    $genderScore = 0;

    // создаем массив с условиями проверки пола (женского)
    $femaleConditions = [
        // отчество заканчивается на "вна"
        mb_substr($fullnameAsArray['patronymic'], -3) == 'вна',
        // имя заканчивается на "а"
        mb_substr($fullnameAsArray['name'], -1) == 'а',
        // фамилия заканчивается на "ва"
        mb_substr($fullnameAsArray['surname'], -2) == 'ва',
    ];

    // создаем массив с условиями проверки пола (мужского)
    $maleConditions = [
        // отчество заканчивается на "ич"
        mb_substr($fullnameAsArray['patronymic'], -2) == 'ич',
        // имя заканчивается на "й" или "н"
        mb_substr($fullnameAsArray['name'], -1) == 'й' || mb_substr($fullnameAsArray['name'], -1) == 'н',
        // фамилия заканчивается на "в"
        mb_substr($fullnameAsArray['surname'], -1) == 'в',
    ];

    // проверяем по условиям женского пола, и снижаем очки, если true
    foreach ($femaleConditions as $condition) {
        if ($condition) {
            $genderScore--;
        }
    }

    // проверяем по условиям мужского пола и увеличиваем очки, если true
    foreach ($maleConditions as $condition) {
        if ($condition) {
            $genderScore++;
        }
    }

    // возвращаем результат (1 == мужской пол, -1 == женский, 0 == пол не определен)
    return $genderScore <=> 0;
}

// функция принимает массив с данными о пользователях и возвращает состав по полу в процентах в виде HEREDOC строки
function getGenderDescription($personsArray)
{

    // создаем массив, в который будет передаваться информация о поле человека после обработки массива с именами
    $genderComposition = [];

    // определяем пол каждого человека из массива с именами и записываем информацию о поле в массив $genderComposition
    foreach ($personsArray as $index => $person) {
        // проверяем количество "очков пола", на основе имени пользователя
        $score = getGenderFromName($person['fullname']);
        if ($score > 0) {
            // если очки больше 0, записываем в массив $genderComposition информацию о том, что пользователь - мужчина
            $genderComposition[$index] = 'male';
        } elseif ($score < 0) {
            // если очков меньше 0, пользователь - женщина
            $genderComposition[$index] = 'female';
        } else {
            // очки == 0, пол пользователя не удалось определить
            $genderComposition[$index] = 'unknown';
        }
    }

    // перезаписываем массив, посчитав количество вхождений каждого пола
    $genderComposition = array_count_values($genderComposition);

    // считаем количество людей в массиве
    $numberOfPeople = count($personsArray);

    // вычисляем процент мужчин среди людей в массиве и округляем до 1 знака после запятой
    $percentageOfMale = round($genderComposition['male'] / $numberOfPeople * 100, 1);
    // процент женщин
    $percentageOfFemale = round($genderComposition['female'] / $numberOfPeople * 100, 1);
    // процент людей, пол которых не удалось определить
    $percentageOfUnknown = round($genderComposition['unknown'] / $numberOfPeople * 100, 1);

    //формируем строку с итоговым результатом
    return <<<RESULT
    Гендерный состав аудитории:
    ---------------------------
    Мужчины - $percentageOfMale%
    Женщины - $percentageOfFemale%
    Не удалось определить - $percentageOfUnknown%
    RESULT;
}

// Функция принимает ФИО в виде отдельных строк и массив с данными о пользователях. Возвращает строку, в которой содержится сокращенное имя пользователя №1, сокращенное имя пользователя №2, который случайно выбирается из массива с пользователями по критерию пола (пол должен быть противоположным к пользователю №1), а так же процент соответствия (случайное число от 50 до 100)
function getPerfectPartner($surname, $name, $patronymic, $personsArray)
{
    // Помещаем ФИО-аргументы в массив
    $mainPersonFullName = [
        $surname,
        $name,
        $patronymic,
    ];

    // Приводим каждую часть ФИО к нужному формату
    foreach ($mainPersonFullName as &$partOfName) {
        $partOfName = mb_convert_case($partOfName, MB_CASE_TITLE);
    }

    // Склеиваем ФИО в одну строку
    $mainPersonFullName = getFullnameFromParts(...$mainPersonFullName);

    // Определяем пол
    $mainPersonGender = getGenderFromName($mainPersonFullName);

    // выбираем случайную пару для пользователя из массива
    // создаем функцию по извлечению случайного пользователя из массива
    function getRandomPerson($array) {
        // создаем случайный индекс на основе длины массива
        $randomIndex = mt_rand(0, count($array) -1);
        // возвращаем имя пользователя из подмассива по этому индексу
        return $array[$randomIndex]['fullname'];
    }

    // создаем цикл для нахождения пользователя противоположного пола
    do {
        // выбираем случайное имя из массива
        $randomPerson = getRandomPerson($personsArray);
        // определяем пол на основе имени
        $randomPersonGender = getGenderFromName($randomPerson);
    } while ($randomPersonGender == $mainPersonGender || $randomPersonGender == 0); // если пол случайного пользователя совпадает с поло основного или не определен, повторяем цикл

    // готовим данные для вывода результата
    
    // случайный процент соответствия пары
    $matchPercentage = mt_rand(5000, 10000) / 100 . '%';
    
    // сокращаем имена для вывода результата
    $names = [getShortName($mainPersonFullName), getShortName($randomPerson)];

    // выводим результат
    return <<<RESULT
    $names[0] + $names[1] =
    \u{2661} Идеально на $matchPercentage \u{2661}
    RESULT;
}

/** ПРОВЕРЯЕМ РАБОТУ ФУНКЦИЙ */

print_r("// Функция getPartsFromFullname \n");
print_r(getPartsFromFullname('Иванов Иван Иванович'));

print_r("\n\n// Функция getFullnameFromParts \n");
print_r(getFullnameFromParts('Иванов', 'Иван', 'Иванович'));

print_r("\n\n// Функция getShortName \n");
print_r(getShortName('Иванов Иван Иванович'));

print_r("\n\n// Функция getGenderFromName \n");
print_r(getGenderFromName('Иванов Иван Иванович'));

print_r("\n\n// Функция getGenderDescription \n");
print_r(getGenderDescription($example_persons_array));

print_r("\n\n// Функция getPerfectPartner \n");
print_r(getPerfectPartner('иванов', 'ИВАН', 'иВнОвИч', $example_persons_array));
