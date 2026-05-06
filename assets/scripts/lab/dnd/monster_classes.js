// monster_classes.js

export const monsterClasses = [
  {
	  "name": "Bandit",
	  "type": {
		"category": "Humanoïde",
		"size": "M",
		"alignment": "tout alignement non loyal",
		"race": "toute race"
	  },
	  "armor_class": {
		"value": 12,
		"description": "armure de cuir"
	  },
	  "hit_points": {
		"average": 11,
		"dice": "2d8 + 2"
	  },
	  "speed": {
		"walk": "9 m"
	  },
	  "abilities": {
		"strength": {
		  "score": 11,
		  "modifier": 0
		},
		"dexterity": {
		  "score": 12,
		  "modifier": 1
		},
		"constitution": {
		  "score": 12,
		  "modifier": 1
		},
		"intelligence": {
		  "score": 10,
		  "modifier": 0
		},
		"wisdom": {
		  "score": 10,
		  "modifier": 0
		},
		"charisma": {
		  "score": 10,
		  "modifier": 0
		}
	  },
	  "senses": {
		"passive_perception": 10
	  },
	  "languages": [
		"une langue au choix (généralement le commun)"
	  ],
	  "challenge_rating": {
		"cr": "1/8",
		"xp": 25
	  },
	  "actions": [
		{
		  "name": "Cimeterre",
		  "type": "attaque au corps à corps avec une arme",
		  "attack_bonus": 3,
		  "reach": "1,50 m",
		  "target": "une cible",
		  "hit": {
			"average_damage": 4,
			"damage_dice": "1d6 + 1",
			"damage_type": "tranchants"
		  }
		},
		{
		  "name": "Arbalète légère",
		  "type": "attaque à distance avec une arme",
		  "attack_bonus": 3,
		  "range": {
			"normal": "24 m",
			"long": "96 m"
		  },
		  "target": "une cible",
		  "hit": {
			"average_damage": 5,
			"damage_dice": "1d8 + 1",
			"damage_type": "perforants"
		  }
		}
	  ],
	  "description": [
		"Les bandits vagabondent en bandes et sont parfois dirigés par des malfrats, des vétérans ou des mages. Tous les bandits ne sont pas mauvais. L'oppression, la sécheresse, les épidémies ou la famine peuvent souvent entraîner d'honnêtes gens vers une vie de banditisme.",
		"Les pirates sont des bandits de haute mer. Ils peuvent être des flibustiers intéressés uniquement par les trésors et le meurtre, ou être des corsaires légitimés par la couronne pour attaquer et piller les navires d'une nation ennemie."
	  ]
	}
];
