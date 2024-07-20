/*
 * Author: Abdullah A Almsaeed
 * Date: 4 Jan 2014
 * Description:
 *      This is a demo file used only for the main dashboard (index.html)
 **/

$(function () {

    'use strict'

    // Make the dashboard widgets sortable Using jquery UI
    $('.connectedSortable').sortable({
    placeholder         : 'sort-highlight',
    connectWith         : '.connectedSortable',
    handle              : '.card-header, .nav-tabs',
    forcePlaceholderSize: true,
    zIndex              : 999999
    })
    $('.connectedSortable .card-header, .connectedSortable .nav-tabs-custom').css('cursor', 'move')


    $.getJSON('home/json', function(data) {
        console.log(data);
        var months = data.orders_price_months;
        var markets = data.orders_price_markets;

        $.each(months, function(index, value) {
            console.log(value.price + ": " + value.month);
        });


        /* Chart.js Charts */

        // Donut Chart

        var donut_markets = [];
        var donut_prices = [];
        $.each(markets, function(index, value) {
            donut_markets[index] = value.market_name;
            donut_prices[index] = value.price;
        });
        console.log(donut_markets);
        console.log(donut_prices);

        var pieChartCanvas = $('#sales-chart-canvas').get(0).getContext('2d')
        var pieData        = {
            labels: donut_markets,
            datasets: [
                {
                    data: donut_prices,
                    backgroundColor : ['#f56954', '#00a65a', '#f39c12', '#ffffff', '#ffffff', '#ffffff', '#ffffff', '#ffffff', '#ffffff'],
                }
            ]
        }
        var pieOptions = {
            legend: {
                display: true
            },
            maintainAspectRatio : false,
            responsive : true,
        }
        //Create pie or douhnut chart
        // You can switch between pie and douhnut using the method below.
        var pieChart = new Chart(pieChartCanvas, {
            type: 'doughnut',
            data: pieData,
            options: pieOptions
        });


        // Sales graph chart

        var graph_months = [];
        var graph_prices = [];
        $.each(months, function(index, value) {
            graph_months[index] = value.month;
            graph_prices[index] = value.price;
        });
        console.log(graph_months);
        console.log(graph_prices);

        var salesGraphChartCanvas = $('#line-chart').get(0).getContext('2d');
        //$('#revenue-chart').get(0).getContext('2d');

        var salesGraphChartData = {
            labels  : graph_months,
            datasets: [
                {
                    label               : 'Digital Goods',
                    fill                : false,
                    borderWidth         : 2,
                    lineTension         : 0,
                    spanGaps : true,
                    borderColor         : '#efefef',
                    pointRadius         : 3,
                    pointHoverRadius    : 7,
                    pointColor          : '#efefef',
                    pointBackgroundColor: '#efefef',
                    data                : graph_prices
                }
            ]
        }

        var salesGraphChartOptions = {
            maintainAspectRatio : false,
            responsive : true,
            legend: {
                display: false,
            },
            scales: {
                xAxes: [{
                    ticks : {
                        fontColor: '#efefef',
                    },
                    gridLines : {
                        display : false,
                        color: '#efefef',
                        drawBorder: false,
                    }
                }],
                yAxes: [{
                    ticks : {
                        stepSize: 100,
                        fontColor: '#efefef',
                    },
                    gridLines : {
                        display : true,
                        color: '#efefef',
                        drawBorder: false,
                    }
                }]
            }
        }

        // This will get the first returned node in the jQuery collection.
        var salesGraphChart = new Chart(salesGraphChartCanvas, {
                type: 'line',
                data: salesGraphChartData,
                options: salesGraphChartOptions
            }
        )


    });
    // END - $.getJSON('home/json'


});
// END - $(function ()
