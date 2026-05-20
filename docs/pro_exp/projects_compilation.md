# Projects Compilation

# RAIDGBS Research Project

## Period

February 2010 – June 2010

## Context

This project was carried out as part of my final year thesis during my Bachelor's degree in Computer Science.

The work took place at the CBMN (Centre de Biophysique Moléculaire Numérique) in Gembloux and was part of the RAIDGBS research program.

RAIDGBS focused on the analysis of genetic sequences of Group B Streptococcus (GBS), a bacterium that can cause serious infections in newborns when transmitted from the mother during pregnancy or childbirth.

The broader objective of the research program was to better understand the genetic variability of these bacteria in order to contribute to the development of faster and more affordable diagnostic tests.

My work focused on building the data processing system used to collect and structure biological sequence data used in this research.

## Objective

Design and implement a system capable of retrieving DNA sequence data from public biological databases and storing them in a structured local database.

The system needed to automate the retrieval and processing of these datasets so that researchers could easily access and work with the collected information.

## My Role

I was the main developer responsible for implementing the system during my final year internship.

The project was carried out under the supervision of researcher Sven Steinhauer, who had previously defined the research objectives, the data sources and the technological approach.

My work focused on the technical implementation of the system, including database design, data processing scripts and the automation of the data ingestion workflow.

## Responsibilities

- Implementation of the data ingestion system
- Database schema design
- Development of Perl scripts to retrieve and process biological datasets
- Generation of SQL files used to populate the database
- Implementation of the Bash script orchestrating the ingestion workflow
- Testing and validation of the data ingestion process
- Documentation of the system as part of the academic thesis

## Technical Challenges

- Learning Perl at the beginning of the project in order to develop the data processing scripts
- Retrieving and processing biological datasets from public databases
- Designing a database schema adapted to store DNA sequence information
- Automating the ingestion pipeline using Perl and Bash scripts
- Handling performance issues when processing large datasets
- Optimizing SQL queries to significantly reduce the overall execution time of the ingestion process

## Technologies Used

Languages
- Perl
- SQL
- Bash

Database
- MySQL

Data Sources
- Public biological databases

Environment
- Linux

Tools
- NetBeans
- PhpMyAdmin

## Technical Implementation

The system implemented a data ingestion pipeline retrieving biological datasets from public databases.

Perl scripts were responsible for retrieving the datasets, transforming the data and generating SQL files containing the insertion queries.

A Bash script orchestrated the entire workflow, including:

- creation of the database schema
- creation of the database tables
- execution of the SQL insertion scripts
- application of integrity constraints once the data had been inserted

This allowed the database to be recreated from scratch and ensured the full ingestion process could be executed automatically.

### Performance Optimization

While processing larger datasets, execution time became an issue during the ingestion process.

Several improvements were implemented:

- reduction of unnecessary generated SQL queries
- use of batch insert strategies instead of executing many individual insert statements
- postponing the creation of foreign key constraints until after the bulk data insertion

These optimizations significantly reduced the execution time of the ingestion pipeline, bringing it down from several hours to only a few minutes.

This was one of my first practical experiences dealing with database performance and data ingestion optimization.

## Outcomes / Impact

The project resulted in a working prototype capable of importing experimental datasets into a structured database.

The system automated a large part of the data ingestion process and provided a foundation for further research data analysis.

## Key Learnings

- First experience designing a database schema from scratch
- Implementation of automated data processing scripts
- Collaboration with researchers in a scientific environment
- Exposure to data engineering concepts before entering the web development industry

---

# Sogesa ERP System

## Period

November 2011 – December 2014
see -> `experiences/sogesa.md`

## Context

Sogesa is a consulting company specialized in the management and operation of agricultural land on behalf of landowners.

Many landowners do not have the expertise or resources required to manage agricultural land themselves. 
Sogesa acts as an intermediary between landowners and the various actors involved in agricultural production.

Agronomists employed by Sogesa are responsible for 
- analyzing the soil
- deciding which crops should be planted
- coordinating field preparation
- selecting phytosanitary treatments
- organizing harvesting
- managing the sale of agricultural products.

At the time I joined the company, very few digital tools existed to support these activities. 
The goal of the project was to progressively build an internal software system allowing agronomists to manage their work and later extend the platform to support the company's operational management.

## Users

The system was used internally by the members of the company:

- 4 agronomists
- 1 secretary
- the company director
- the director’s assistant

In total, the platform supported around 7 internal users involved in agricultural operations and company administration.

## Objective

Design and develop an internal web-based ERP system supporting the daily activities of agronomists and the operational management of the company.

The system initially focused on digitizing the workflow used by agronomists in the field, including the management of land plots, agricultural operations and coordination with external actors.

Over time, the platform expanded to include additional business functions such as invoicing, product ordering and stock management.

## Key Business Concepts

Several important domain concepts had to be modeled in the system:

- Agricultural plots and parcels, which could evolve over time
- Crop years ("années culture"), which follow seasonal agricultural cycles rather than calendar years
- Agricultural operations performed on specific parcels
- Work orders sent to agricultural contractors

The system supported the management of multiple crop years simultaneously and allowed agronomists to adapt parcel structures from one season to another.

## My Role

I was the main developer responsible for building the first version of the system.

During the first year of the project, I was the only developer working on the application. 
Later, a second junior developer joined the project and we continued developing the system together for several years.

My work involved understanding the agronomists’ workflows, designing the database structure and progressively implementing the application features required to support their daily activities.

An external technical code audit was performed during the project in order to review the quality of the implementation and provide recommendations regarding architecture and development practices.

## Responsibilities

- Understanding and documenting the agronomists' workflows
- Translating business processes into software features
- Designing the database schema
- Developing the web application from scratch
- Implementing modules for managing agricultural plots, operations and related entities
- Implementing business management features such as invoicing and stock management
- Deploying and maintaining the application infrastructure with an external hosting provider
- Collaborating with agronomists and other stakeholders to refine the system

## Key Features

- Management of agricultural plots and parcels
- Support for multiple crop years
- Creation of agricultural work orders
- Export of work orders to Excel for field use
- Invoicing management
- Product ordering and stock management
- Role-based access for agronomists and administrative staff

## Technical Challenges

- Learning PHP and web application architecture while building the system
- Designing and implementing a full web application starting from scratch
- Understanding a complex business domain (agricultural land management)
- Translating real-world processes into software models
- Managing the technical infrastructure and application deployment
- Participating in long meetings with domain experts to fully understand their workflows
- Learning how to estimate development tasks and plan implementation work
- Adopting more modern practices after receiving feedback from an external code audit

## Technologies Used

Languages
- PHP 5.3
- SQL
- JavaScript
- HTML
- CSS

Frameworks / Libraries
- Symfony 2 (introduced later for the administrative part of the application)
- jQuery
- Bootstrap

Database
- MySQL

Tools
- Doctrine ORM
- Admin bundle for Symfony (for the management interface)
- SVN first and Git later
- XAMPP
- Netbeans IDE

Infrastructure
- External hosting provider

## Deployment

The deployment process evolved during the project:

- Initially deployments were performed manually using FTP
- Version control was later introduced using SVN
- The project eventually migrated to Git
- Deployments were then performed manually through SSH access to the hosting server

## Outcomes / Impact

The project resulted in a custom ERP system used internally by Sogesa to support both agronomists' field operations and the company's administrative management.

The platform progressively replaced manual processes and improved the coordination between agronomists and other actors involved in agricultural production.

The system continued to be used and further developed after my departure.

Despite being initially developed by junior developers without senior technical leadership, the system proved robust enough to remain in active production use many years after its initial development.

## Key Learnings

- First professional experience building a full web application
- Learning PHP and web backend development in a production environment
- Understanding how to translate real-world business processes into software systems
- Experience collaborating directly with domain experts
- First exposure to software architecture and code quality considerations

---

# Publifund Platform

## Period

January 2015 – September 2015  
see → `experiences/02_exp_f2c.md`

## Organization
Financial Communication Consult (F2C)

## Project Context

Publifund was an internal platform developed by Financial Communication Consult (F2C), a company specialized in financial information distribution for investment funds.

The platform aggregated data related to investment funds from multiple external sources and generated standardized regulatory documents known as **KIID (Key Investor Information Documents)**. These documents are required by European financial regulations and must follow strict formatting and content rules.

The generated information and documents were distributed to financial information providers and media platforms such as Bloomberg and other financial data distributors.

The system therefore served as a **data aggregation, document generation and distribution platform for the investment fund industry**.

---

## Project Objectives

- Collect data related to investment funds from external sources
- Process and normalize the collected data
- Generate regulatory KIID documents in PDF format
- Archive generated documents
- Provide structured data and documents to financial media platforms
- Offer internal tools to search and manage investment fund data

---

## System Overview

The platform operated roughly as a data processing pipeline:

External data sources  
→ data collection (scraping or APIs)  
→ parsing and normalization  
→ storage in relational database  
→ KIID document generation  
→ archival in document database  
→ distribution to external financial media

---

## My Role

Backend developer within a small development team.

Main responsibilities included:

- Development and maintenance of KIID document generators
- Implementation and updates of PDF generation logic
- Maintenance of complex listing pages displaying financial data
- Participation in parsing logic for data collected by automated bots
- Maintenance and evolution of internal tools, including a custom ticketing system
- Working within an existing monolithic PHP codebase

---

## Team Structure

Small in-house technical team composed of approximately seven members:

- Lead developer
- Senior developer
- Backend developer
- Full-stack developer
- Two frontend developers
- System administrator

I joined the team as one of the junior developers.

Development priorities and planning were mainly coordinated by the lead developer together with the company management.

---

## Technologies Used

### Main Languages
- PHP
- SQL

### Data Storage
- MySQL (storage of financial data)
- MongoDB (archival storage of generated documents)

### Document Generation
- PDFLib (generation of KIID PDF documents)

### Frontend
- HTML
- CSS
- JavaScript

### Infrastructure
- Linux servers
- Cron jobs for automated processes

---

## Data Processing

The system relied on automated bots responsible for collecting information about investment funds from external sources.

These bots periodically executed tasks similar to the following workflow:

Cron job  
→ data scraping or API retrieval  
→ parsing and normalization  
→ storage in MySQL

Generated KIID documents were then created from this structured data.

---

## Document Generation System

KIID documents followed strict regulatory structures defined by financial authorities.

The system implemented a flexible generation approach:

- A default KIID generator for most funds
- Custom generators for specific funds when needed
- Generation logic implemented in PHP using PDFLib

Once generated, documents were archived in MongoDB in order to:

- keep historical versions
- allow fast retrieval
- serve documents to external clients and internal users

---

## Data Volume

The document archive stored in MongoDB reached **hundreds of gigabytes of data**, reflecting the large number of generated documents and historical versions maintained by the system.

---

## Technical Challenges

### Working with a Large Existing Codebase

The platform had been developed internally over several years without relying on mainstream frameworks.  
Understanding and navigating this monolithic architecture required significant time and effort.

### Performance Constraints

Generating regulatory documents efficiently was an important concern due to the potentially large number of documents requested by external clients and media platforms.

Some implementation choices prioritized raw performance in document generation.

### Financial Domain Complexity

The project required understanding how investment fund information is structured and how regulatory KIID documents must be generated.

This involved working with:

- structured financial data
- strict document formats
- regulatory constraints on document content

### Integration with External Data Sources

The system depended on external sources for financial data, requiring reliable parsing and normalization of incoming information.

---

## Outcomes / Impact

The Publifund platform served as a central system for aggregating investment fund data, generating regulatory KIID documents and distributing structured financial information to external media platforms.

The system handled large volumes of generated documents and maintained a significant document archive.

---

## Personal Learnings

This project exposed me to:

- development within an established production system
- maintenance of a large monolithic codebase
- performance considerations in document generation
- financial data processing pipelines
- collaboration within a structured development team

It was also my first experience working on a platform responsible for producing regulatory financial documents at scale.

---

# Easy4Pro

## Period
July 2016 - September 2016
see `experiences/03_exp_adneom.md`

## Organization
Flash Global (mission through Adneom)

## Project Context

Easy4Pro was a prototype web platform developed at Flash Global, a logistics company specialized in the management of critical spare parts and international supply chain services.

The project aimed to explore a system capable of orchestrating urgent transportation operations by connecting multiple logistics providers across different regions.

The platform was based on an existing internal logistics application and extended it with new capabilities intended to automate parts of the transport coordination process.

I joined the project as a consultant developer through Adneom and worked directly within Flash Global’s development team.

---

## Project Objectives

- Develop a prototype platform for urgent logistics orchestration
- Extend an existing logistics system with new capabilities
- Implement features specific to the Easy4Pro concept
- Integrate the new prototype into an existing large codebase

---

## System Overview

The platform allowed users to define two locations anywhere in the world and attempted to coordinate the transport of goods between them by automatically identifying available logistics actors along the route.

The system aimed to support time-constrained deliveries by orchestrating several transport providers involved in different stages of the delivery chain.

---

## My Role

Consultant backend developer integrated into Flash Global’s internal development team.

Main responsibilities included:

- contributing to the development of the Easy4Pro prototype
- implementing and adapting features on top of an existing codebase
- working on backend logic and some frontend components
- navigating and understanding a large existing application architecture

---

## Team Structure

Development was carried out by a mixed team composed of:

- internal Flash Global developers
- consultants from Adneom
- an external development company from France

The team size was roughly under ten developers.

---

## Technologies Used

### Main Languages
- PHP
- JavaScript
- SQL

### Framework
- Zend Framework

### Frontend
- HTML
- CSS
- JavaScript  
- Possibly Backbone.js (memory uncertain)

### Database
- MySQL

### Version Control
- Git (GitFlow workflow)

---

## Technical Challenges

### Understanding a Large Existing System

The project was based on a substantial existing application, requiring significant effort to understand the architecture and integrate new functionality.

### Working on a Prototype Built from a Legacy Codebase

Easy4Pro was developed as a proof-of-concept derived from an existing logistics platform, combining elements of refactoring, extension and experimentation.

### Complex Domain Logic

The application attempted to model and coordinate multiple actors involved in international logistics operations, which introduced significant domain complexity.

---

## Outcomes / Impact

Easy4Pro served as a prototype exploring new ways to orchestrate urgent logistics operations using an existing software platform.

The project allowed Flash Global to experiment with extending their logistics systems toward more automated transport coordination.

---

## Personal Learnings

This project was my first consulting mission after joining Adneom.

It introduced me to:

- working as a consultant embedded within a client organization
- collaborating with mixed teams composed of internal developers and external partners
- navigating a large existing enterprise codebase
- using Git with the GitFlow branching workflow

It was also my first professional experience working abroad during the week (Luxembourg) while returning home on weekends.

---

# Logic-Immo Image Delivery Platform

## Period
November 2016 – June 2017

## Organization
Logic-Immo (mission through Adneom)

## Project Context

Logic-Immo was one of the main real estate listing platforms in Belgium, competing with major players such as Immoweb.

Real estate listings contained a large number of images, which represented a significant portion of the website's bandwidth and loading time.

The objective of the project was to design and implement a new system for storing and delivering listing images in order to improve page loading performance on the Logic-Immo website.

The project was developed as a relatively independent service from the rest of the platform.

---

## Project Objectives

- redesign the storage system for real estate listing images
- improve global delivery performance of images
- create a scalable infrastructure capable of serving large volumes of media files
- provide a REST API to manage and retrieve images
- integrate the new system with the existing Logic-Immo platform

---

## System Overview

The project consisted of designing a dedicated service responsible for managing and distributing images associated with real estate listings.

The architecture included:

existing images in legacy system  
→ migration / mapping  
→ storage in AWS infrastructure  
→ image delivery through CDN  
→ access through REST API

A database was used to maintain the mapping between images stored in the legacy platform and the new storage infrastructure.

---

## My Role

Backend developer working alongside a lead developer (freelance consultant).

Main responsibilities included:

- design and development of REST API endpoints
- implementation of backend logic using Symfony
- integration with the image storage infrastructure
- implementation of the image mapping system between legacy and new storage
- participation in the overall design of the service

The mission was carried out on-site within the Logic-Immo development environment.

---

## Team Structure

Small dedicated team composed of:

- one lead developer (freelance)
- myself as backend developer consultant

We interacted with the internal Logic-Immo technical leadership when necessary, but the project was developed relatively independently from the main platform.

---

## Technologies Used

### Main Languages
- PHP
- SQL

### Framework
- Symfony

### Backend Architecture
- REST API

### Infrastructure
- AWS (image storage)
- CDN for global image delivery
- Docker (first exposure)

### Database
- relational database used for image mapping

### Version Control
- Git

---

## Technical Challenges

### Image Performance at Scale

Real estate platforms rely heavily on images, which can significantly impact page loading times.

The project aimed to redesign the image infrastructure to provide faster and more reliable image delivery.

### Designing a Dedicated Media Service

The project required designing a new service responsible for image storage and delivery, separate from the existing platform.

### Integration with Existing Systems

The new infrastructure had to remain compatible with the existing Logic-Immo platform and its existing image data.

### Distributed Media Delivery

The system relied on cloud storage and CDN distribution in order to deliver images efficiently to users.

---

## Outcomes / Impact

The project resulted in the creation of a dedicated service responsible for storing and distributing listing images more efficiently.

This new architecture aimed to reduce image loading times and improve overall performance of the Logic-Immo platform.

---

## Personal Learnings

This project marked several important milestones in my technical career:

- first experience building a project from scratch using Symfony
- first implementation of a REST API in a production system
- first exposure to cloud-based infrastructure (AWS)
- first exposure to Docker in a development environment

It also gave me experience working on performance-related challenges in a media-heavy web platform.

---

# Projects at Isobar

## Period
July 2017 – February 2018

## Organization
Isobar

## Context

Isobar is a digital agency specialized in developing digital platforms, marketing websites and campaign-related web services for corporate clients.

During my time at Isobar, I worked on multiple short-term client projects involving different brands and technology stacks. 
The agency context required frequent context switching and rapid delivery under tight deadlines.

---

## Nature of the Work

My work involved contributing to several client projects, mainly focusing on the implementation of features, maintenance of existing web platforms and delivery of campaign-related digital assets.

Projects were often short and required adapting quickly to different codebases and client requirements.

---

## Client Projects

### AB InBev Brand Websites

Development and maintenance work on several brand websites belonging to the AB InBev group, including:

- Leffe
- Jupiler
- Hoegaarden

Tasks included:

- updating website content
- implementing new features
- maintaining existing functionality

---

### Event-Pulse Platform

Participation in maintenance and development work on **Event-Pulse**, a platform used for managing events.

Work involved maintaining existing features and improving parts of the platform.

---

### Côte d'Or Email Marketing Campaign

Implementation of an email marketing template for a Côte d'Or campaign.

---

### Honda Event Website

Participation in the development of a website created for Honda during a major event campaign.

---

### Promotional Code Generator

Implementation of a system used to generate promotional codes for a beverage brand campaign.

---

## Team Structure

The development team evolved during my time at Isobar.

Initially, the team consisted of:

- two developers
- later joined by an additional developer
- a project owner responsible for coordination

---

## Technologies Encountered

During this period I worked with a variety of technologies, including:

### Main Languages
- PHP
- JavaScript
- SQL

### Frameworks / CMS
- Symfony
- Drupal
- Zend
- Laravel
- WordPress

### Frontend
- HTML
- CSS
- JavaScript
- jQuery

### Databases
- MySQL

---

## Development Environment

- Git
- Jira
- Confluence
- daily team meetings

---

## Characteristics of the Work

Working in a digital agency environment involved:

- frequent switching between multiple projects
- short delivery cycles
- adapting to different client platforms and codebases
- collaboration with multidisciplinary teams

---

## Personal Learnings

This experience exposed me to the dynamics of agency work, where developers often need to adapt quickly to different projects and client contexts.

It also highlighted the challenges of working under tight delivery deadlines and managing multiple concurrent projects.

---

# Delcampe Marketplace Platform

## Period
April 2018 – March 2019

## Organization
Delcampe (mission through Blubird)
see `experiences\05_exp_blubird.md`

## Project Context

Delcampe is an online marketplace dedicated to collectibles such as stamps, postcards, coins and other collector items.

The platform connects sellers and buyers worldwide and manages listings, transactions, messaging and user interactions.

In addition to the web marketplace, Delcampe also provides a desktop application used by professional sellers.  
This application connects to the platform through a mix of REST and SOAP APIs to synchronize marketplace data.

I joined the development team as a consultant through Blubird and contributed to the maintenance and evolution of the main marketplace platform.

---

## Project Objectives

- maintain and improve the core marketplace platform
- fix bugs and ensure system stability
- improve existing features and pages
- contribute to new feature development
- maintain high code quality within a large production codebase

---

## System Architecture

The platform was built as a **modular monolithic application** based on the Symfony framework.

The system included several interconnected components:

- web marketplace platform
- APIs used by internal services and the desktop client
- internationalized user interface
- internal services supporting marketplace operations

---

## My Role

Backend developer integrated into the main development team.

Responsibilities included:

- fixing bugs affecting the marketplace platform
- implementing improvements to existing pages and components
- participating in feature development
- collaborating with frontend developers when features required both backend and frontend work
- contributing to code reviews and technical discussions

Over the course of the mission, I worked on different parts of the system depending on priorities.

---

## Team Structure

The software department was organized into two closely collaborating teams.

### Product Team

- product lead
- senior product expert
- innovation / product strategy role
- two frontend developers

### Development Team

- lead developer
- technical lead
- two internal developers
- three consultants from Blubird (including myself)
- three freelance developers

This structure helped maintain strong alignment between product decisions and technical implementation.

---

## Development Practices

The team followed an Agile workflow inspired by Scrum practices:

- sprint planning cycles (2–3 weeks)
- daily stand-up meetings
- poker planning for task estimation
- sprint retrospectives
- technical design discussions for complex features

The development process also included:

- feature branches for each bug fix or feature
- systematic code reviews before merging
- controlled release branches for production deployments

This workflow ensured stability and maintainability of the production platform.

---

## Technologies Used

### Backend

- PHP 7 (avec OOP et principes SOLID)
- Symfony 3.1
- MariaDB

### APIs

- REST APIs
- SOAP services (used by the desktop application)

### Frontend Tooling

- JavaScript
- Gulp
- NPM
- Bower

### Testing

Automated testing was an important part of the project:

- PHPUnit for backend unit tests
- Jasmine for JavaScript testing
- CasperJS for browser-based automated tests

### Monitoring

- Sentry for application error monitoring

### Version Control & CI/CD

- Git (hosted on internal GitLab)
- feature branches per bug or feature
- mandatory code reviews
- GitLab CI/CD pipelines

Pipelines executed automated tests before allowing releases to proceed.

### Containerization

- Docker used within the CI/CD pipeline environment

### Project Management & Documentation

- Jira for ticket management
- Confluence for documentation and knowledge sharing

### Infrastructure

Hosting and infrastructure management were handled internally by the company.

---

## Technical Challenges

### Working on a Mature Marketplace Platform

The project involved contributing to a large and long-lived production system serving an international community of users.

Understanding the architecture and navigating the existing codebase required collaboration with experienced team members.

### High Code Quality Standards

The development team maintained strong expectations regarding:

- code structure
- testing
- development workflows
- review processes

### Multi-language Platform

The marketplace supported multiple languages and international users, increasing application complexity.

---

## Outcomes / Impact

My work contributed to maintaining and improving the stability and quality of the Delcampe marketplace platform.

Through bug fixing, incremental improvements and feature development, the team continued evolving the platform while maintaining reliability for its large user base.

---

## Personal Learnings

This mission was one of the most formative experiences in my early career.

It allowed me to:

- experience a well-structured development environment
- discover the practical impact of Agile and Scrum-inspired workflows
- work within a large Symfony production application
- understand the importance of automated testing in long-term projects
- experience the positive impact of a healthy and collaborative development team

---

# Keytrade Bank – Web Platforms Maintenance

## Period
April 2019 – July 2019

## Organization
Keytrade Bank (mission through Blubird)

## Project Context

Keytrade Bank is a Belgian online bank providing digital banking services and online trading tools.

During this mission, I joined a small PHP development team responsible for maintaining several web platforms used by the bank.

These platforms were distinct but interconnected:

- the **public website**, used as a marketing and onboarding portal for potential clients
- the **extranet**, a private web application used by bank customers once their account was created
- the **intranet**, an internal administrative interface used by bank employees to manage certain customer operations

The financial transaction systems themselves were managed by other internal systems and were not part of the applications I worked on.

---

## System Architecture

The applications were built using **PHP 5.6**, with a customized version of PHP compiled and certified for the bank's security requirements.

The systems relied on a legacy architecture without a modern framework and were maintained within a traditional monolithic codebase.

Core components included:

- PHP backend applications
- MySQL databases
- internal deployment infrastructure
- integration with other internal banking systems

---

## My Role

Backend developer integrated as support for the PHP maintenance team.

My responsibilities included:

- fixing bugs across the public site, extranet and intranet applications
- investigating production issues reported through internal ticketing
- implementing small corrections and improvements
- supporting the existing team in maintaining legacy PHP systems

The mission primarily focused on **maintenance and debugging activities**.

---

## Team Structure

The PHP development team was relatively small:

- a technical lead acting as both architect and department coordinator
- one freelance consultant
- myself as a consultant through Blubird

Additional roles existed outside the team:

- a **QA team**, responsible for validation (no direct collaboration in day-to-day development)
- a **release manager**, responsible for SVN merges and release validation

Developers were not authorized to perform production merges themselves.

---

## Technologies Used

### Backend
- PHP 5.6 (custom security-certified build)

### Database
- MySQL

### Version Control
- SVN

### DevOps
- Jenkins (legacy version used for deployments)

### Project Management
- Jira

---

## Development Practices

The development process followed a relatively traditional workflow.

Key characteristics included:

- centralized control of merges in SVN
- code reviews performed manually by the release manager
- Jenkins-based deployment process
- ticket-driven bug fixing and maintenance

The process was not Agile in the modern sense and followed a more continuous maintenance workflow.

---

## Technical Challenges

### Legacy PHP Codebase

The applications were built on a large legacy codebase with limited architectural structure.

Understanding the system and implementing fixes required careful navigation of the existing code to avoid introducing regressions.

### Security Constraints

Due to the banking environment, the PHP runtime itself was a custom compiled and security-certified build.

This imposed additional constraints on the development environment and deployment processes.

---

## Outcomes / Impact

My work supported the stability of several web platforms used by Keytrade Bank by fixing bugs and maintaining existing functionality.

The mission helped reduce the workload on the small internal PHP team responsible for these systems.

---

## Personal Learnings

Although the mission was short, it provided exposure to the internal workings of a banking organization.

Key takeaways included:

- discovering the operational environment of a digital bank
- understanding the constraints that regulated sectors can impose on technology evolution
- working within a legacy PHP codebase maintained under strict operational procedures

The experience also highlighted the gap that can exist between modern development practices and legacy systems maintained in highly regulated environments.

---

# Famille Chrétienne — Editorial Platform (DPI)

**Company:** Contraste Digital  
**Client:** Groupe Famille Chrétienne  
**Role:** Backend Developer  
**Duration:** ~1 year, through the year 2020
**Stack:** PHP, Drupal 7, DPI platform, Bash

---

## Context

The project involved deploying **DPI**, an editorial platform developed by Contraste Digital on top of Drupal 7.

DPI was originally created for the Belgian media group **Rossel** (publisher of *Le Soir*) and progressively evolved into a complex platform supporting multiple digital news websites.

The platform provides several core capabilities required for online media operations:

- article publication and editorial workflows
- homepage and section page composition
- advertisement integration
- external content feeds
- subscription management
- user account management

In the Rossel ecosystem, journalists write articles using a dedicated editorial system.  
Articles are stored in a separate system and then synchronized with Drupal/DPI, which renders them on the website according to their editorial context (sections, homepage placement, etc.).

Famille Chrétienne, a growing media organization, needed a more scalable and maintainable platform to support the development of its digital presence.

---

## Project Objective

The objective of the project was to transform the Rossel-specific implementation of DPI into a **white-label platform** that could be reused by other media organizations.

Once this generic version was created, the platform was customized and deployed for the **Famille Chrétienne** website.

---

## My Contributions

### Platform Decoupling

Worked on extracting Rossel-specific dependencies from the existing DPI codebase in order to create a **media-agnostic version** of the platform.

Key tasks included:

- removing hardcoded configuration values
- making certain features configurable
- adapting page structures originally designed for Rossel media websites

The goal was to create a reusable base installation of DPI that could be adapted for other publishers.

---

### Deployment Automation

Designed and implemented a **Bash installation script** capable of bootstrapping a new DPI instance from an empty directory.

The script automated several steps of the platform setup process and simplified the deployment of new media websites based on the DPI platform.

---

### Custom Development for Famille Chrétienne

Contributed to adapting the platform to the specific needs of the Famille Chrétienne editorial website.

Tasks included:

- backend development on Drupal/DPI modules
- configuration and customization of the platform
- implementation of custom features

Example:

- implemented a scheduled task retrieving a **daily biblical quote** and publishing it automatically on a dedicated page.

---

## Team

- 2 backend developers
- collaboration with a developer experienced with the DPI platform

---

## Technical Challenges

The main challenge was working with a **large legacy platform originally designed for a specific media group**.

Instead of performing a full architectural refactor, the work focused on **progressively decoupling Rossel-specific logic** and making key components configurable in order to enable reuse across different media organizations.

This approach allowed the team to deliver a working white-label version of DPI within the constraints of the project timeline.

---

# Stanhome — E-commerce Platform (France & Italy)

**Company:** Contraste Digital  
**Client:** Stanhome  
**Role:** Backend Developer  
**Duration:** 2021 – 2022 - roughly 2 years for both projects
**Stack:** PHP, Drupal 7, Drupal Commerce, ElasticSearch, Google Maps API, Salesforce

---

## Context

Stanhome is a European direct-selling company specializing in home care and beauty products.

The project involved modernizing the company's e-commerce platform, starting with the **French market** and later extending to **Italy**.

Stanhome operates partly through a **direct-sales network model**, where independent sellers organize product presentation events and sell products directly to customers (similar to "Tupperware-style" home meetings).  
The platform therefore needed to support both traditional e-commerce capabilities and features aligned with this distribution model.

---

## Project Objectives

Key objectives included:

- redesigning the visual theme of the e-commerce platform
- improving performance when browsing large product catalogs
- optimizing the purchase funnel to improve conversion
- implementing tools allowing users to locate nearby distributors
- enabling faster marketing operations such as product promotions
- integrating sales data with the company CRM system

After the French platform was delivered, the architecture was reused and adapted for the **Italian market**.

---

## My Contributions

### Product Catalog Optimization

Improved catalog browsing performance by integrating **ElasticSearch**.

ElasticSearch was used for:

- product search
- **faceted navigation**
- product filtering within the catalog

This significantly improved the performance and usability of product browsing.

---

### Checkout / Sales Funnel Improvements

Contributed to improvements in the **purchase funnel**, helping streamline the checkout process and improve the user experience.

---

### Dealer Locator

Implemented a **dealer locator** feature using **Google Maps API**, allowing users to find nearby distributors based on their geographic location.

This feature supported Stanhome's direct-sales distribution model.

---

### Marketing Automation

Participated in the implementation of mechanisms enabling **semi-automated updates of the product catalog**, allowing marketing teams to activate promotions more quickly.

This included backend logic and **cron-based synchronization tasks** within the Drupal platform.

---

### CRM Integration

Worked on the integration between the e-commerce platform and the company's **Salesforce CRM**.

Sales-related data was transmitted from the Drupal Commerce platform to Salesforce, enabling internal teams to track sales activity and customer interactions within the CRM ecosystem.

---

### Multi-country Deployment

After the French implementation, participated in adapting the platform for the **Italian market**, reusing the architecture developed for France while adapting certain features to local requirements.

---

## Team

Worked as part of a development team within Contraste Digital collaborating with the client's product and technical stakeholders.

---

## Technical Challenges

The project required balancing **e-commerce performance, marketing flexibility, and international deployment**.

Key challenges included:

- handling large product catalogs efficiently
- implementing fast product filtering through ElasticSearch
- supporting Stanhome's hybrid **e-commerce + direct sales network** model
- adapting the architecture for multiple markets while maintaining a shared technical base.

---

# Hôpitaux Iris Sud — Institutional Website & Intranet

**Company:** Contraste Digital  
**Client:** Hôpitaux Iris Sud (HIS)  
**Role:** Backend Developer  
**Duration:** 03/2022 – 07/2022  
**Stack:** PHP, Drupal 9, OAuth2, ADFS, Progenda

---

## Context

Hôpitaux Iris Sud (HIS) is a network of four hospitals located in the Brussels region.

The project consisted in building a **new unified website** representing the four hospitals of the group.  
The goal was to centralize communication towards the public and standardize how the different hospital sites were presented online.

The platform included:

- a **public institutional website** providing information about services and practitioners
- an **intranet section** allowing hospital staff to manage internal communications.

---

## Project Objectives

The main objectives of the project were:

- providing a unified digital presence for the four hospitals
- simplifying the search for practitioners across all hospital locations
- facilitating the appointment booking process
- enabling internal communication management for hospital staff.

---

## My Contributions

### Drupal Platform Setup

Participated in setting up the **Drupal 9 CMS** used for both the public website and the intranet.

Responsibilities included:

- Drupal installation and configuration
- multilingual setup
- backend development of custom features.

---

### Practitioner Search & Appointment Assistance Tool

Implemented a **search interface helping users find the appropriate practitioner**.

The tool allowed users to filter results through three criteria:

- medical service
- specialist
- hospital location

Once a practitioner was selected, users could proceed with the **appointment booking process via Progenda integration**.

---

### SSO Integration (ADFS)

Developed a **custom Drupal module** implementing authentication through **ADFS (Active Directory Federation Services)** using OAuth2.

This allowed hospital staff to access the intranet using their existing organizational credentials.

Additional access restrictions were implemented through **IP-based access control**.

---

### Access Management

Implemented authentication and access control mechanisms allowing hospital staff to manage internal communication content.

---

## Team

Small multidisciplinary team composed of:

- 1 UI/UX designer
- 1 front-end integrator
- the HIS IT manager
- myself as the main backend developer

Additional support was provided by my IT director **Didier Lahousse** for the ADFS integration.

---

## Technical Challenges

The main technical challenges encountered during the project were:

- designing and implementing the **practitioner search and appointment assistance tool**
- integrating **ADFS authentication through a custom Drupal module**

The authentication integration required adapting Drupal’s authentication system to work with the hospital's identity infrastructure.

---

# Rossel — MoveIT (SSO Platform)

**Company:** Contraste Digital  
**Client:** Rossel Group  
**Role:** Backend Developer  
**Duration:** 11/2023 – 05/2024  
**Stack:** Go, Gin, Kafka, Docker, Azure, Azure DevOps, ELK Stack

---

## Context

Rossel is one of the largest media groups in Belgium, operating multiple newspapers and digital media platforms including *Le Soir*.

The **MoveIT project** aimed at redesigning the user management and authentication system across the Rossel ecosystem.

The objective was to build a **new scalable Single Sign-On (SSO) platform** capable of supporting all Rossel media brands.

The new system was designed to progressively replace existing authentication mechanisms, starting with *Le Soir* and later expanding to other newspapers of the group.

---

## Project Objectives

Key objectives included:

- building a **scalable SSO system** for the Rossel media ecosystem
- supporting user authentication across multiple publications
- improving performance and scalability for millions of users
- enabling future expansion to additional media brands.

The project adopted a **microservices architecture** to ensure long-term scalability and modularity.

---

## My Contributions

### Microservices Development

Participated in the development of backend services using **Go** and the **Gin framework**.

Implemented **two microservices** responsible for user-related functionalities within the new authentication system.

Adopted structured request handling using **DTO (Data Transfer Object) patterns** to ensure clear separation between API contracts and internal business logic.

---

### Event-driven Communication

Contributed to the design and implementation of **Kafka message structures** used for communication between services.

The architecture combined:

- **REST APIs** for synchronous interactions
- **Kafka event queues** for asynchronous communication.

---

### User Registration System

Contributed to the development of services supporting the **new user registration workflow** used by Rossel websites.

These services were consumed by the front-end applications implementing the new user onboarding funnel.

---

### Observability and Logging

Worked with the **ELK stack (Elasticsearch, Logstash, Kibana)** to monitor and analyze logs generated by the microservices architecture.

This setup allowed centralized log collection and improved debugging across the distributed system.

---

## Architecture

The system relied on a **containerized microservices architecture** including:

- Go-based backend services
- Gin framework
- Docker containers
- Kafka for event-driven communication
- REST APIs for service interactions
- ELK stack for log aggregation and monitoring.

Services were deployed on **Azure infrastructure** using **Azure DevOps CI/CD pipelines**.

---

## Team

Large multi-team organization including:

- infrastructure / DevOps team
- several backend development teams responsible for different service domains
- front-end team responsible for the user registration experience.

I worked within two Scrum teams:

- the **user registration team** (~6–7 members)
- the **microservices backend team** (~5–6 developers).

---

## Technical Challenges

The project required designing and implementing a **highly scalable authentication system** capable of supporting multiple media brands.

Key challenges included:

- implementing reliable communication between services
- defining robust Kafka message schemas
- ensuring scalability and performance for the future Rossel ecosystem
- monitoring distributed services through centralized logging.

---

# Marge Delhaize — Data Integration & Margin Analysis Platform

**Role:** Lead Developer  
**Client:** Independent Delhaize store owners  
**Duration:** 09/2025 – Present  
**Stack:** Symfony 7, React, PostgreSQL, Docker, REST API, PHPUnit, PHPStan, Vitest

---

## Context

Independent Delhaize store owners rely on several internal systems to manage their operations.

However, these systems do not communicate directly with each other and provide only partial visibility into product profitability.

The project aims to build a platform capable of:

- collecting data from multiple operational systems
- consolidating this information into a unified data model
- providing a clear view of **real product margins over time**.

This allows store owners to better understand the gap between **estimated margins communicated by Delhaize** and **actual margins observed in their operations**.

The platform is designed to support decision-making at the level of individual stores or groups of stores.

---

## Data Sources

The system integrates data from three operational systems used by Delhaize stores:

### Store Office
Database containing product reference information used across the Delhaize ecosystem.

Access to the data is limited and does not currently provide a straightforward export mechanism.

---

### BabbleWay
System containing purchase information from product suppliers.

Data exports are available in structured formats such as CSV or Excel.

---

### StoreLine
Point-of-sale system containing sales transactions recorded at store checkouts.

Exports are also available in CSV or Excel format.

---

## Project Objective

The platform acts as an **ETL pipeline and analytical interface**:

1. Extract data from multiple external systems
2. Normalize and structure the data in a central database
3. Compute product-level margins
4. Provide visualization tools for store managers.

The long-term goal is to enable:

- near real-time margin monitoring
- cross-store margin comparison
- improved purchasing negotiations for groups of store owners.

---

## Architecture

The system follows a **modular web architecture**.

### Backend

- Symfony 7 application
- REST API exposing data services
- PostgreSQL for centralized data storage.

### Frontend

- React application
- communication with backend via REST API.

### Infrastructure

- Docker containers for each component
- automated test suites for both frontend and backend.

---

## Testing

Several automated testing tools are used:

Backend:
- PHPUnit
- PHPStan

Frontend:
- Vitest.

These tools help maintain code quality and ensure reliability during development.

---

## Use of AI-assisted Development

Development of the project involves the use of **AI-assisted programming tools**, notably Claude Code.

The project workflow includes several specialized AI agents responsible for tasks such as:

- task planning and backlog management
- backend code implementation
- code review
- automated test generation
- frontend component development and review.

These agents assist the development workflow but the overall architecture and implementation decisions remain under human supervision.

---

## My Contributions

- Lead development of the backend platform using Symfony 7
- Design and implementation of the REST API
- Implementation of the data storage model using PostgreSQL
- Development of the React frontend interface
- Implementation of automated tests
- Integration of AI-assisted development workflows.

The project is developed in collaboration with my brother, who focuses primarily on data acquisition and system architecture.

---

## Project Status

The project started in **September 2025**.

The development of the application progressed significantly until **December 2025**, after which progress slowed due to difficulties accessing data from the external systems.

Following recent discussions with the client, access to the required data sources may now be possible, allowing work on the extraction pipeline to resume.

---

# Programming Coaching & Technical Mentoring

**Role:** Programming Coach / Technical Mentor  
**Platform:** Superprof.be  
**Duration:** 10/2021 – Present  

---

## Context

Since October 2021, I have been providing private programming coaching sessions for students and professionals seeking to improve their technical skills.

The coaching activity started as a complementary professional activity and progressively evolved into a regular mentoring practice.

Sessions are organized mainly through the **Superprof** platform, with additional presence on **Apprentus** and **Malt**.

---

## Audience

Participants come from diverse backgrounds, including:

- computer science students preparing for exams
- professionals needing additional technical support in their job
- individuals transitioning into software development
- developers working on personal or professional projects requiring guidance.

The mentoring approach adapts to the learner’s context and technical background.

---

## Topics Covered

Coaching sessions cover a broad range of software development topics, including:

- web development fundamentals
- PHP and backend development
- HTML, JavaScript, and front-end basics
- SQL and database design
- HTTP protocols and API interactions
- AJAX and asynchronous programming
- Git and version control
- software architecture concepts
- development best practices
- project structuring and debugging strategies.

---

## Session Format

Sessions typically last **two hours**, which allows meaningful technical progress while maintaining focus.

Different formats are used depending on the learner's needs:

- remote sessions via video conferencing (Zoom or similar tools)
- in-person coaching at the learner’s location
- in-person coaching at my workspace.

Sessions often involve:

- live coding
- code review
- debugging assistance
- explanation of technical concepts
- guidance on project architecture.

---

## Activity Volume

- First coaching session: **October 11, 2021**
- Approximately **698 hours of coaching delivered between 2022 and 2025**
- Around **10 students mentored** across different contexts.

The activity continues today with an average of **two sessions per week**.

---

## Key Contributions

- Helping learners overcome technical blockers
- Structuring explanations of complex programming concepts
- Guiding learners through real development projects
- Supporting professional development and technical autonomy.

---

## Personal Motivation

This activity combines several aspects that I value in my professional practice:

- sharing knowledge and experience
- meeting new people from diverse backgrounds
- strengthening my own technical mastery through teaching
- helping others progress and gain confidence in programming.