(function(angular, $, _) {

  // Nb. directive MUST start with lowercase letter.
  angular.module('pelf').directive('pelfProspectsList', ['crmApi', '$timeout', function(crmApi, $timeout, pelf) {
    return {
      scope: {
        pelf: '='
      },
      controller: ['$scope', '$location', 'crmApi', function ($scope, $location, crmApi) {
        console.log("pelfProspectsList received Pelf object as ", $scope.pelf, $scope.pelf.friendlyProspectStage);
        // We need to load our data.
        crmApi('activity', 'GetPelfProspect', [])
        .then(function(result) {
          console.log("prospects loaded:", result);
          $scope.prospects = result.values;
        });
      }], // end of controller.
      link: function(scope, elem, attrs) {
      },
      templateUrl: '~/pelf/ProspectsList.html',
    };
  }]);

})(angular, CRM.$, CRM._);

